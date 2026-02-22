"""API Tester Agent

Reads a Bruno collection, executes HTTP requests against the API, and generates a Markdown report.

Usage:
    python .agents/scripts/api-tester/agent.py --base-url http://localhost:8083/ --collection docs/bruno/workout-app/v1
    python .agents/scripts/api-tester/agent.py --config .agents/scripts/api-tester/config.json --folder periodization
"""
from __future__ import annotations

import dataclasses
import json
import os
import sys
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path

import click
import httpx

sys.path.insert(0, str(Path(__file__).parent.parent))

from base.agent import BaseAgent
from base.config import AgentConfig


@dataclass
class ApiTesterConfig(AgentConfig):
    base_url: str = "http://localhost:8083/"
    collection_path: str = "docs/bruno"
    api_key: str = ""
    bearer_token: str = ""
    expected_status: dict = field(
        default_factory=lambda: {
            "index": 200,
            "store": 201,
            "show": 200,
            "update": 200,
            "destroy": 204,
        }
    )
    report_output: str = ".agents/reports/api-test-{date}.md"

    @classmethod
    def from_json(cls, path: str) -> "ApiTesterConfig":
        with open(path) as f:
            data = json.load(f)
        known = {f.name for f in dataclasses.fields(cls)}
        return cls(**{k: v for k, v in data.items() if k in known})

    @classmethod
    def from_env(cls, prefix: str = "AGENT_") -> "ApiTesterConfig":
        return cls(
            model=os.getenv(f"{prefix}MODEL", "claude-sonnet-4-6"),
            max_iterations=int(os.getenv(f"{prefix}MAX_ITERATIONS", "15")),
            log_file=os.getenv(f"{prefix}LOG_FILE"),
            base_url=os.getenv(f"{prefix}BASE_URL", "http://localhost:8083/"),
            collection_path=os.getenv(f"{prefix}COLLECTION_PATH", "docs/bruno"),
            api_key=os.getenv("AGENT_API_KEY", ""),
            bearer_token=os.getenv("AGENT_BEARER_TOKEN", ""),
        )


class ApiTesterAgent(BaseAgent):
    def __init__(self, config: ApiTesterConfig, project_root: str = ".") -> None:
        self.project_root = Path(project_root).resolve()
        # Resolve sensitive values from env if still placeholder
        if not config.api_key:
            config.api_key = os.getenv("AGENT_API_KEY", "")
        if not config.bearer_token:
            config.bearer_token = os.getenv("AGENT_BEARER_TOKEN", "")
        super().__init__(config)

    def get_system_prompt(self) -> str:
        cfg: ApiTesterConfig = self.config
        return f"""You are an API tester that validates REST endpoints by running HTTP requests.

## Configuration
- Base URL: {cfg.base_url}
- Authentication: X-API-KEY header + Bearer token (already set in the http_request tool)

## How to read .bru files
Extract: HTTP verb (get/post/put/delete), url, body:json content, params:query fields.
Substitutions:
  - `{{{{base_url}}}}` → `{cfg.base_url}`
  - `:param` → use a concrete ID (1 by default, or an ID obtained from a prior create call)
  - Do NOT send empty query params unless testing filtering behavior

## Expected status codes
```json
{json.dumps(cfg.expected_status, indent=2)}
```
For verbs/actions not listed, expect 200.

## Test strategy
1. For each folder in the collection, test endpoints in this order: index → store → show → update → destroy
2. Capture the ID returned from store calls and use it for show/update/destroy
3. For nested resources (URL contains multiple `:param`), create/use a parent resource first
4. Record each result: endpoint, method, expected status, actual status, pass/fail

## Report format
Generate a Markdown report with:
- Summary table (total tests, passed, failed)
- Detailed table per folder:

| Endpoint | Method | Expected | Actual | Result |
|----------|--------|----------|--------|--------|
| /api/v1/... | GET | 200 | 200 | ✅ PASS |
| /api/v1/... | POST | 201 | 422 | ❌ FAIL |

- Notes section for any unexpected errors or observations

## Process
1. Use list_directory to navigate the collection
2. Use read_file to read .bru files (skip folder.bru files)
3. Use http_request to execute each request
4. Collect all results, then use write_report to save the Markdown report
5. Return a brief summary of results"""

    def setup_tools(self) -> None:
        self.register_tool(
            name="read_file",
            fn=self._read_file,
            description="Read a file from the project",
            input_schema={
                "type": "object",
                "properties": {"path": {"type": "string"}},
                "required": ["path"],
            },
        )
        self.register_tool(
            name="list_directory",
            fn=self._list_directory,
            description="List directory contents",
            input_schema={
                "type": "object",
                "properties": {"path": {"type": "string"}},
                "required": ["path"],
            },
        )
        self.register_tool(
            name="http_request",
            fn=self._http_request,
            description="Execute an HTTP request against the API",
            input_schema={
                "type": "object",
                "properties": {
                    "method": {
                        "type": "string",
                        "enum": ["GET", "POST", "PUT", "PATCH", "DELETE"],
                        "description": "HTTP method",
                    },
                    "url": {"type": "string", "description": "Full URL to request"},
                    "json_body": {
                        "type": "object",
                        "description": "JSON body (for POST/PUT)",
                    },
                    "query_params": {
                        "type": "object",
                        "description": "Query string parameters",
                    },
                    "extra_headers": {
                        "type": "object",
                        "description": "Additional headers to merge",
                    },
                },
                "required": ["method", "url"],
            },
        )
        self.register_tool(
            name="write_report",
            fn=self._write_report,
            description="Save the test report as a Markdown file",
            input_schema={
                "type": "object",
                "properties": {"content": {"type": "string", "description": "Markdown content"}},
                "required": ["content"],
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

    def _http_request(
        self,
        method: str,
        url: str,
        json_body: dict | None = None,
        query_params: dict | None = None,
        extra_headers: dict | None = None,
    ) -> str:
        cfg: ApiTesterConfig = self.config
        headers: dict[str, str] = {"Accept": "application/json", "Content-Type": "application/json"}
        if cfg.api_key:
            headers["X-API-KEY"] = cfg.api_key
        if cfg.bearer_token:
            headers["Authorization"] = f"Bearer {cfg.bearer_token}"
        if extra_headers:
            headers.update(extra_headers)

        try:
            with httpx.Client(timeout=30) as client:
                response = client.request(
                    method=method.upper(),
                    url=url,
                    headers=headers,
                    json=json_body,
                    params=query_params,
                )
            try:
                body = response.json()
            except Exception:
                body = response.text[:1000]

            return json.dumps({"status": response.status_code, "body": body})
        except Exception as exc:
            return json.dumps({"error": str(exc)})

    def _write_report(self, content: str) -> str:
        date_str = datetime.now(timezone.utc).strftime("%Y-%m-%d_%H%M")
        path_str = self.config.report_output.replace("{date}", date_str)
        p = self._safe_path(path_str)
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text(content, encoding="utf-8")
        return f"Report saved: {path_str}"


# ── CLI ───────────────────────────────────────────────────────────────────────

@click.command()
@click.option("--config", "config_path", default=None, help="Path to config JSON file")
@click.option("--base-url", default=None, help="API base URL")
@click.option("--collection", default=None, help="Path to Bruno collection directory")
@click.option("--folder", default=None, help="Specific collection folder to test (e.g. periodization)")
@click.option("--model", default=None, help="Claude model to use")
@click.option("--project-root", default=".", show_default=True, help="Project root directory")
@click.option("--log-file", default=None, help="Path to JSON log file")
def main(
    config_path: str | None,
    base_url: str | None,
    collection: str | None,
    folder: str | None,
    model: str | None,
    project_root: str,
    log_file: str | None,
) -> None:
    """Test API endpoints by running requests from a Bruno collection."""
    config = (
        ApiTesterConfig.from_json(config_path)
        if config_path
        else ApiTesterConfig.from_env()
    )
    config.apply_overrides(
        base_url=base_url,
        collection_path=collection,
        model=model,
        log_file=log_file,
    )

    agent = ApiTesterAgent(config, project_root=project_root)

    target = f"{config.collection_path}/{folder}" if folder else config.collection_path
    prompt = (
        f"Test all API endpoints in the Bruno collection at: {target}\n\n"
        f"Base URL: {config.base_url}\n\n"
        "Navigate the collection, execute every request in order (index→store→show→update→destroy), "
        "capture created resource IDs for subsequent calls, and generate a full Markdown report."
    )

    print(f"API Tester — model: {config.model}, target: {target}")
    result = agent.run(prompt)
    print("\n" + result)


if __name__ == "__main__":
    main()
