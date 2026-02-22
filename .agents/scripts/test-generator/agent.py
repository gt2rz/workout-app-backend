"""Test Generator Agent

Analyzes Laravel controllers and Form Requests to generate Pest 4 feature tests.

Usage:
    python .agents/scripts/test-generator/agent.py --feature Periodization
    python .agents/scripts/test-generator/agent.py --config .agents/scripts/test-generator/config.json
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
class TestGeneratorConfig(AgentConfig):
    feature: str = ""
    controllers_dir: str = "app/Features/{feature}/Controllers"
    requests_dir: str = "app/Features/{feature}/Requests"
    existing_tests_dir: str = "tests/Feature"
    output_dir: str = "tests/Feature/{feature}"
    test_framework: str = "pest4"

    def resolve(self) -> None:
        """Replace {feature} placeholders with the actual feature name."""
        if not self.feature:
            return
        self.controllers_dir = self.controllers_dir.replace("{feature}", self.feature)
        self.requests_dir = self.requests_dir.replace("{feature}", self.feature)
        self.output_dir = self.output_dir.replace("{feature}", self.feature)

    @classmethod
    def from_json(cls, path: str) -> "TestGeneratorConfig":
        with open(path) as f:
            data = json.load(f)
        known = {f.name for f in dataclasses.fields(cls)}
        return cls(**{k: v for k, v in data.items() if k in known})

    @classmethod
    def from_env(cls, prefix: str = "AGENT_") -> "TestGeneratorConfig":
        return cls(
            model=os.getenv(f"{prefix}MODEL", "claude-sonnet-4-6"),
            max_iterations=int(os.getenv(f"{prefix}MAX_ITERATIONS", "20")),
            log_file=os.getenv(f"{prefix}LOG_FILE"),
            feature=os.getenv(f"{prefix}FEATURE", ""),
        )


class TestGeneratorAgent(BaseAgent):
    def __init__(self, config: TestGeneratorConfig, project_root: str = ".") -> None:
        self.project_root = Path(project_root).resolve()
        config.resolve()
        super().__init__(config)

    def get_system_prompt(self) -> str:
        return """You are a Pest 4 test generator for Laravel REST APIs.

Analyze Laravel controllers and Form Request classes to generate comprehensive, runnable Pest feature tests.

## Critical Project Conventions (follow exactly)

### Authentication setup in beforeEach:
```php
beforeEach(function () {
    $this->user = User::factory()->create();
    $apiKey = $this->user->createToken('test')->plainTextToken;
    $this->actingAs($this->user, 'sanctum')
        ->withHeaders(['X-API-KEY' => $apiKey]);
});
```

### Test file header:
```php
<?php

use App\Models\User;
use App\Models\ModelName;
// other model imports as needed
```

### Test structure pattern:
```php
describe('ModelController', function () {
    describe('index', function () {
        it('returns paginated list', function () {
            ModelName::factory()->count(3)->create(['user_id' => $this->user->id]);

            $this->getJson('/api/v1/route')
                ->assertOk()
                ->assertJsonStructure(['data' => [['id', 'field']]]);
        });
    });

    describe('store', function () {
        it('creates a new resource', function () {
            $response = $this->postJson('/api/v1/route', [
                'required_field' => 'value',
            ]);

            $response->assertCreated()
                ->assertJsonPath('data.required_field', 'value');
        });

        it('validates required fields', function () {
            $this->postJson('/api/v1/route', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['required_field']);
        });
    });

    describe('show', function () {
        it('returns a resource', function () {
            $resource = ModelName::factory()->create(['user_id' => $this->user->id]);

            $this->getJson("/api/v1/route/{$resource->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $resource->id);
        });

        it('returns 404 for non-existent resource', function () {
            $this->getJson('/api/v1/route/99999')
                ->assertNotFound();
        });

        it('prevents access to another users resource', function () {
            $otherUser = User::factory()->create();
            $resource = ModelName::factory()->create(['user_id' => $otherUser->id]);

            $this->getJson("/api/v1/route/{$resource->id}")
                ->assertForbidden();
        });
    });

    describe('update', function () {
        it('updates a resource', function () {
            $resource = ModelName::factory()->create(['user_id' => $this->user->id]);

            $this->putJson("/api/v1/route/{$resource->id}", ['field' => 'new_value'])
                ->assertOk()
                ->assertJsonPath('data.field', 'new_value');
        });
    });

    describe('destroy', function () {
        it('deletes a resource', function () {
            $resource = ModelName::factory()->create(['user_id' => $this->user->id]);

            $this->deleteJson("/api/v1/route/{$resource->id}")
                ->assertNoContent();

            $this->assertDatabaseMissing('table_name', ['id' => $resource->id]);
        });
    });
});
```

## Important notes
- Use `==` not `===` for ID comparisons (PostgreSQL returns strings)
- API routes are under `/api/v1/` prefix
- assertOk()=200, assertCreated()=201, assertNoContent()=204, assertNotFound()=404, assertForbidden()=403, assertUnprocessable()=422
- For nested resources, create the parent first and use its ID in the URL
- Use `User::factory()->create()` not `User::factory()->make()` for persisted records
- Use model factories, check existing factory states before manually setting attributes

## Your process
1. Read 1-2 existing test files from tests/Feature/ (other features) to learn the EXACT patterns used
2. Read the target controller(s) to understand: routes, method signatures, authorization
3. Read all Form Request files to know required/optional fields and validation rules
4. Read the model factories to know what states/fields are available
5. Read routes.php for the feature to understand exact URL structure
6. Generate one test file per controller, following the exact conventions found
7. Each test file must be complete and runnable — no placeholders or TODOs"""

    def setup_tools(self) -> None:
        self.register_tool(
            name="read_file",
            fn=self._read_file,
            description="Read a file",
            input_schema={
                "type": "object",
                "properties": {"path": {"type": "string", "description": "Path relative to project root"}},
                "required": ["path"],
            },
        )
        self.register_tool(
            name="list_directory",
            fn=self._list_directory,
            description="List contents of a directory",
            input_schema={
                "type": "object",
                "properties": {"path": {"type": "string"}},
                "required": ["path"],
            },
        )
        self.register_tool(
            name="write_file",
            fn=self._write_file,
            description="Write a test file",
            input_schema={
                "type": "object",
                "properties": {
                    "path": {"type": "string"},
                    "content": {"type": "string"},
                },
                "required": ["path", "content"],
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


# ── CLI ───────────────────────────────────────────────────────────────────────

@click.command()
@click.option("--config", "config_path", default=None, help="Path to config JSON file")
@click.option("--feature", default=None, help="Feature name (e.g. Periodization, Workout)")
@click.option("--output", default=None, help="Output directory for generated tests")
@click.option("--model", default=None, help="Claude model to use")
@click.option("--project-root", default=".", show_default=True, help="Project root directory")
@click.option("--log-file", default=None, help="Path to JSON log file")
def main(
    config_path: str | None,
    feature: str | None,
    output: str | None,
    model: str | None,
    project_root: str,
    log_file: str | None,
) -> None:
    """Generate Pest 4 feature tests from Laravel controllers and Form Requests."""
    config = (
        TestGeneratorConfig.from_json(config_path)
        if config_path
        else TestGeneratorConfig.from_env()
    )

    # Apply CLI overrides before resolve() runs in the agent __init__
    if feature:
        config.feature = feature
    if output:
        config.output_dir = output
    config.apply_overrides(model=model, log_file=log_file)

    if not config.feature:
        raise click.UsageError("Feature name is required (--feature or AGENT_FEATURE env var).")

    agent = TestGeneratorAgent(config, project_root=project_root)

    prompt = (
        f"Generate Pest 4 feature tests for the '{config.feature}' feature.\n\n"
        f"- Controllers: {config.controllers_dir}\n"
        f"- Form Requests: {config.requests_dir}\n"
        f"- Existing tests (for pattern reference): {config.existing_tests_dir}\n"
        f"- Output directory: {config.output_dir}\n\n"
        "Steps:\n"
        "1. Read 1-2 existing test files from other features to learn the project's exact test pattern\n"
        "2. Read the feature's routes.php to understand URLs and resource nesting\n"
        "3. Read each controller and its Form Requests\n"
        "4. Check database/factories/ for relevant model factories\n"
        "5. Generate one test file per controller — complete, runnable, no placeholders"
    )

    print(f"Test Generator — model: {config.model}, feature: {config.feature}")
    result = agent.run(prompt)
    print("\n" + result)


if __name__ == "__main__":
    main()
