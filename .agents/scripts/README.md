# Laravel AI Agents

Suite of autonomous AI agents (Claude API + tool use) for automating Laravel development workflows.
Reusable across projects — copy `.agents/scripts/` to any Laravel project and adjust the config.

## Agents

| Agent | Purpose |
|-------|---------|
| `bruno-generator` | Generate Bruno `.bru` collection files from Laravel routes + Form Requests |
| `api-tester` | Execute HTTP requests against the API and produce a Markdown test report |
| `test-generator` | Generate Pest 4 feature tests from Laravel controllers and Form Requests |

---

## Setup

```bash
# 1. Install dependencies
pip install -r .agents/scripts/requirements.txt

# 2. Set your Anthropic API key
export ANTHROPIC_API_KEY=sk-ant-...

# 3. For api-tester, also set auth credentials
export AGENT_API_KEY=your-x-api-key
export AGENT_BEARER_TOKEN=your-bearer-token
```

---

## Bruno Generator

Analyzes `routes.php` and `Requests/` files in each Laravel feature and generates Bruno `.bru` files.

```bash
# Generate files for a single feature
python .agents/scripts/bruno-generator/agent.py \
  --feature Periodization \
  --routes-dir app/Features \
  --output-dir docs/bruno/workout-app/v1

# Use a config file (copy config.example.json → config.json and edit)
python .agents/scripts/bruno-generator/agent.py \
  --config .agents/scripts/bruno-generator/config.json

# All features at once
python .agents/scripts/bruno-generator/agent.py \
  --config .agents/scripts/bruno-generator/config.json
```

### Config (`bruno-generator/config.example.json`)

```json
{
  "model": "claude-sonnet-4-6",
  "max_iterations": 20,
  "routes_dir": "app/Features",
  "output_dir": "docs/bruno/workout-app/v1",
  "base_url_variable": "base_url",
  "auth_mode": "inherit",
  "log_file": ".agents/logs/bruno-generator.log"
}
```

---

## API Tester

Reads a Bruno collection, executes every endpoint, and generates a Markdown report.

```bash
# Test a specific collection folder
python .agents/scripts/api-tester/agent.py \
  --base-url http://localhost:8083/ \
  --collection docs/bruno/workout-app/v1 \
  --folder periodization

# Test the full collection
python .agents/scripts/api-tester/agent.py \
  --config .agents/scripts/api-tester/config.json
```

Reports are saved to `.agents/reports/api-test-YYYY-MM-DD_HHMM.md`.

### Config (`api-tester/config.example.json`)

```json
{
  "model": "claude-sonnet-4-6",
  "max_iterations": 25,
  "base_url": "http://localhost:8083/",
  "collection_path": "docs/bruno/workout-app/v1",
  "expected_status": { "index": 200, "store": 201, "show": 200, "update": 200, "destroy": 204 },
  "report_output": ".agents/reports/api-test-{date}.md",
  "log_file": ".agents/logs/api-tester.log"
}
```

---

## Test Generator

Reads controllers + Form Requests + existing tests to generate complete Pest 4 feature test files.

```bash
# Generate tests for a feature
python .agents/scripts/test-generator/agent.py \
  --feature Workout

# With custom output directory
python .agents/scripts/test-generator/agent.py \
  --feature Periodization \
  --output tests/Feature/Periodization \
  --model claude-opus-4-6
```

### Config (`test-generator/config.example.json`)

```json
{
  "model": "claude-sonnet-4-6",
  "max_iterations": 20,
  "feature": "Periodization",
  "controllers_dir": "app/Features/{feature}/Controllers",
  "requests_dir": "app/Features/{feature}/Requests",
  "existing_tests_dir": "tests/Feature",
  "output_dir": "tests/Feature/{feature}",
  "test_framework": "pest4",
  "log_file": ".agents/logs/test-generator.log"
}
```

---

## Environment variables

All config values can be set via env vars with the `AGENT_` prefix:

```bash
export AGENT_MODEL=claude-opus-4-6
export AGENT_MAX_ITERATIONS=20
export AGENT_LOG_FILE=.agents/logs/agent.log

# api-tester specific
export AGENT_API_KEY=your-api-key
export AGENT_BEARER_TOKEN=your-token
export AGENT_BASE_URL=http://localhost:8083/
```

CLI flags always override config file values, which override env vars.

---

## Adapting to another project

1. Copy `.agents/scripts/` to the new project root
2. Copy the relevant `config.example.json` → `config.json` and update paths
3. Update `routes_dir`, `output_dir`, and `base_url` to match the new project's structure
4. Run with `--project-root /path/to/other/project` if running from a different directory

---

## Architecture

```
base/
├── config.py   # AgentConfig dataclass — from JSON, env vars, or CLI
└── agent.py    # BaseAgent — agentic loop, tool routing, retry, logging
```

Each agent:
- Extends `BaseAgent`, implements `setup_tools()` and `get_system_prompt()`
- Has its own `Config` dataclass extending `AgentConfig`
- Is a standalone `agent.py` with a `click` CLI entry point
- Tools enforce path traversal safety (only files within project root)
