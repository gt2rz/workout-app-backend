"""Bruno Generator Agent

Reads Laravel feature routes + Form Requests and generates Bruno .bru collection files.

Usage:
    python .agents/scripts/bruno-generator/agent.py --feature Periodization
    python .agents/scripts/bruno-generator/agent.py --config .agents/scripts/bruno-generator/config.json
"""
from __future__ import annotations

import dataclasses
import json
import os
import sys
from dataclasses import dataclass
from pathlib import Path

import click

sys.path.insert(0, str(Path(__file__).parent.parent))

from base.agent import BaseAgent
from base.config import AgentConfig


@dataclass
class BrunoGeneratorConfig(AgentConfig):
    routes_dir: str = "app/Features"
    output_dir: str = "docs/bruno"
    base_url_variable: str = "base_url"
    auth_mode: str = "inherit"

    @classmethod
    def from_json(cls, path: str) -> "BrunoGeneratorConfig":
        with open(path) as f:
            data = json.load(f)
        known = {f.name for f in dataclasses.fields(cls)}
        return cls(**{k: v for k, v in data.items() if k in known})

    @classmethod
    def from_env(cls, prefix: str = "AGENT_") -> "BrunoGeneratorConfig":
        return cls(
            model=os.getenv(f"{prefix}MODEL", "claude-sonnet-4-6"),
            max_iterations=int(os.getenv(f"{prefix}MAX_ITERATIONS", "15")),
            log_file=os.getenv(f"{prefix}LOG_FILE"),
            routes_dir=os.getenv(f"{prefix}ROUTES_DIR", "app/Features"),
            output_dir=os.getenv(f"{prefix}OUTPUT_DIR", "docs/bruno"),
            base_url_variable=os.getenv(f"{prefix}BASE_URL_VAR", "base_url"),
            auth_mode=os.getenv(f"{prefix}AUTH_MODE", "inherit"),
        )


class BrunoGeneratorAgent(BaseAgent):
    def __init__(self, config: BrunoGeneratorConfig, project_root: str = ".") -> None:
        self.project_root = Path(project_root).resolve()
        super().__init__(config)

    def get_system_prompt(self) -> str:
        cfg: BrunoGeneratorConfig = self.config
        return f"""You are a Bruno API collection generator for Laravel projects.

Analyze Laravel feature route files and Form Request classes to generate Bruno (.bru) API collection files.

## Bruno .bru File Format

### folder.bru (for every directory):
```
meta {{
  name: folder-name
  seq: <number>
}}

auth {{
  mode: inherit
}}
```

### Request file (GET without body):
```
meta {{
  name: List Resources
  type: http
  seq: 1
}}

get {{
  url: {{{{{cfg.base_url_variable}}}}}api/v1/path
  body: none
  auth: {cfg.auth_mode}
}}

settings {{
  encodeUrl: true
  timeout: 0
}}
```

### Request file (GET with query params):
```
meta {{
  name: List Resources
  type: http
  seq: 1
}}

get {{
  url: {{{{{cfg.base_url_variable}}}}}api/v1/path
  body: none
  auth: {cfg.auth_mode}
}}

params:query {{
  filter_field:
  another_field:
}}

settings {{
  encodeUrl: true
  timeout: 0
}}
```

### Request file (POST/PUT with JSON body):
```
meta {{
  name: Create Resource
  type: http
  seq: 2
}}

post {{
  url: {{{{{cfg.base_url_variable}}}}}api/v1/path
  body: json
  auth: {cfg.auth_mode}
}}

body:json {{
  {{
    "field": "example_value",
    "nullable_field": null
  }}
}}

settings {{
  encodeUrl: true
  timeout: 0
}}
```

### Request file (DELETE):
```
meta {{
  name: Delete Resource
  type: http
  seq: 5
}}

delete {{
  url: {{{{{cfg.base_url_variable}}}}}api/v1/path/:id
  body: none
  auth: {cfg.auth_mode}
}}

settings {{
  encodeUrl: true
  timeout: 0
}}
```

## Key Rules

- Path parameters use `:param` notation (e.g. `:macrocycle`, `:session`, `:id`)
- seq ordering within a folder: List/index=1, Create/store=2, Show=3, Update=4, Delete=5
- Subfolder `folder.bru` gets a seq HIGHER than sibling request files (e.g. 6)
- For nullable fields use `null` as example value; for arrays use `[1]`
- PUT verb for update (Laravel apiResource generates PUT)
- Route::apiResource('parent.child', Controller::class)->scoped() = nested resource

## Laravel Route Patterns

- `Route::apiResource('path', Controller::class)` → 5 CRUD endpoints (index, store, show, update, destroy)
- `Route::apiResource('parent.child', Controller::class)->scoped()` → nested, URL: /parent/:parent/child/:child
- `->only([...])` or `->except([...])` limits which endpoints exist
- Route prefix in routes.php maps to URL prefix

## Your Process

1. First, use list_directory and read_file to explore the collection output directory and read 2-3 existing .bru files to understand the exact format in use
2. Explore the routes directory using list_directory and read_file to discover all features and their routes
3. For each feature with routes, also read the Form Request files to extract field schemas
4. Generate all required .bru files using write_file (use create_directory first if needed)
5. Ensure every directory gets a folder.bru file
6. Confirm completion with a summary of files created"""

    def setup_tools(self) -> None:
        self.register_tool(
            name="read_file",
            fn=self._read_file,
            description="Read the contents of a file",
            input_schema={
                "type": "object",
                "properties": {
                    "path": {"type": "string", "description": "File path relative to project root"}
                },
                "required": ["path"],
            },
        )
        self.register_tool(
            name="list_directory",
            fn=self._list_directory,
            description="List files and subdirectories in a directory",
            input_schema={
                "type": "object",
                "properties": {
                    "path": {"type": "string", "description": "Directory path relative to project root"}
                },
                "required": ["path"],
            },
        )
        self.register_tool(
            name="write_file",
            fn=self._write_file,
            description="Create or overwrite a file with the given content",
            input_schema={
                "type": "object",
                "properties": {
                    "path": {"type": "string", "description": "File path relative to project root"},
                    "content": {"type": "string", "description": "File content"},
                },
                "required": ["path", "content"],
            },
        )
        self.register_tool(
            name="create_directory",
            fn=self._create_directory,
            description="Create a directory and any missing parent directories",
            input_schema={
                "type": "object",
                "properties": {
                    "path": {"type": "string", "description": "Directory path relative to project root"}
                },
                "required": ["path"],
            },
        )

    # ── Tool implementations ──────────────────────────────────────────────────

    def _safe_path(self, relative: str) -> Path:
        resolved = (self.project_root / relative).resolve()
        if not str(resolved).startswith(str(self.project_root)):
            raise ValueError(f"Path traversal blocked: {relative}")
        return resolved

    def _read_file(self, path: str) -> str:
        p = self._safe_path(path)
        if not p.exists():
            return f"Error: file not found: {path}"
        return p.read_text(encoding="utf-8")

    def _list_directory(self, path: str) -> str:
        p = self._safe_path(path)
        if not p.exists():
            return f"Error: directory not found: {path}"
        entries = [
            f"[{'dir' if item.is_dir() else 'file'}] {item.name}"
            for item in sorted(p.iterdir())
        ]
        return "\n".join(entries) if entries else "(empty directory)"

    def _write_file(self, path: str, content: str) -> str:
        p = self._safe_path(path)
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text(content, encoding="utf-8")
        return f"Written: {path}"

    def _create_directory(self, path: str) -> str:
        p = self._safe_path(path)
        p.mkdir(parents=True, exist_ok=True)
        return f"Created directory: {path}"


# ── CLI ───────────────────────────────────────────────────────────────────────

@click.command()
@click.option("--config", "config_path", default=None, help="Path to config JSON file")
@click.option("--feature", default=None, help="Specific feature to process (e.g. Periodization)")
@click.option("--routes-dir", default=None, help="Features directory (default: app/Features)")
@click.option("--output-dir", default=None, help="Bruno output directory")
@click.option("--model", default=None, help="Claude model to use")
@click.option("--project-root", default=".", show_default=True, help="Project root directory")
@click.option("--log-file", default=None, help="Path to JSON log file")
def main(
    config_path: str | None,
    feature: str | None,
    routes_dir: str | None,
    output_dir: str | None,
    model: str | None,
    project_root: str,
    log_file: str | None,
) -> None:
    """Generate Bruno .bru files from Laravel feature routes and Form Requests."""
    config = (
        BrunoGeneratorConfig.from_json(config_path)
        if config_path
        else BrunoGeneratorConfig.from_env()
    )
    config.apply_overrides(
        routes_dir=routes_dir,
        output_dir=output_dir,
        model=model,
        log_file=log_file,
    )

    agent = BrunoGeneratorAgent(config, project_root=project_root)

    if feature:
        prompt = (
            f"Generate Bruno .bru files for the '{feature}' feature only.\n\n"
            f"- Routes file: {config.routes_dir}/{feature}/routes.php\n"
            f"- Form Requests: {config.routes_dir}/{feature}/Requests/\n"
            f"- Output directory: {config.output_dir}\n\n"
            "First, read a few existing .bru files from the collection output directory to understand "
            "the exact format and folder structure already in use. Then generate the new files."
        )
    else:
        prompt = (
            f"Generate Bruno .bru files for all features in: {config.routes_dir}\n\n"
            f"- Output directory: {config.output_dir}\n\n"
            "First, explore the output directory and read existing .bru files to understand the "
            "structure. Then process each feature that has a routes.php file."
        )

    print(f"Bruno Generator — model: {config.model}, project: {project_root}")
    result = agent.run(prompt)
    print("\n" + result)


if __name__ == "__main__":
    main()
