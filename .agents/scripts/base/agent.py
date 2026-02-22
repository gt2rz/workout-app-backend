from __future__ import annotations

import json
import os
import random
import signal
import time
from abc import ABC, abstractmethod
from datetime import datetime, timezone
from typing import Any, Callable

import anthropic

from .config import AgentConfig


class BaseAgent(ABC):
    def __init__(self, config: AgentConfig) -> None:
        self.config = config
        self.client = anthropic.Anthropic(api_key=os.environ["ANTHROPIC_API_KEY"])
        self.messages: list[dict] = []
        self._tools: list[dict] = []
        self._tool_handlers: dict[str, Callable] = {}
        self._shutdown = False
        self._setup_signal_handling()
        self.setup_tools()

    # ── Abstract interface ────────────────────────────────────────────────────

    @abstractmethod
    def setup_tools(self) -> None:
        """Register tools via self.register_tool()."""

    @abstractmethod
    def get_system_prompt(self) -> str:
        """Return the system prompt for this agent."""

    # ── Public API ────────────────────────────────────────────────────────────

    def register_tool(
        self,
        name: str,
        fn: Callable,
        description: str,
        input_schema: dict,
    ) -> None:
        self._tools.append(
            {"name": name, "description": description, "input_schema": input_schema}
        )
        self._tool_handlers[name] = fn

    def run(self, prompt: str) -> str:
        self._log("agent_start", {"model": self.config.model, "prompt_len": len(prompt)})
        self.messages = [{"role": "user", "content": prompt}]
        last_text = ""

        for iteration in range(self.config.max_iterations):
            if self._shutdown:
                break

            try:
                response = self._call_with_retry()
            except Exception as exc:
                self._log("error", {"type": type(exc).__name__, "message": str(exc)})
                raise

            for block in response.content:
                if hasattr(block, "text"):
                    last_text = block.text

            if response.stop_reason == "end_turn":
                self._log("agent_end", {"iterations": iteration + 1, "success": True})
                return last_text

            if response.stop_reason == "tool_use":
                self.messages.append({"role": "assistant", "content": response.content})
                results = self._process_tool_calls(response)
                self.messages.append({"role": "user", "content": results})
            else:
                break

        self._log("agent_end", {"iterations": self.config.max_iterations, "success": False})
        raise RuntimeError(
            f"Agent did not finish within {self.config.max_iterations} iterations."
        )

    # ── Internal helpers ──────────────────────────────────────────────────────

    def _call_with_retry(self, max_retries: int = 3, base_delay: float = 1.0) -> Any:
        last_exc: Exception | None = None
        for attempt in range(max_retries):
            try:
                return self.client.messages.create(
                    model=self.config.model,
                    max_tokens=8096,
                    system=self.get_system_prompt(),
                    tools=self._tools,
                    messages=self.messages,
                )
            except (
                anthropic.RateLimitError,
                anthropic.APIConnectionError,
                anthropic.APIStatusError,
            ) as exc:
                last_exc = exc
                if attempt < max_retries - 1:
                    delay = min(base_delay * (2**attempt), 60.0)
                    jitter = delay * 0.1 * (2 * random.random() - 1)
                    time.sleep(delay + jitter)
        raise last_exc  # type: ignore[misc]

    def _process_tool_calls(self, response: Any) -> list[dict]:
        results = []
        for block in response.content:
            if block.type != "tool_use":
                continue

            t0 = time.monotonic()
            handler = self._tool_handlers.get(block.name)
            try:
                if handler is None:
                    result = f"Error: unknown tool '{block.name}'"
                else:
                    result = handler(**block.input)
            except Exception as exc:
                result = f"Tool error in '{block.name}': {exc}"
                self._log("error", {"type": "ToolError", "tool": block.name, "message": str(exc)})

            duration_ms = int((time.monotonic() - t0) * 1000)
            self._log("tool_call", {"tool": block.name, "duration_ms": duration_ms})

            results.append(
                {"type": "tool_result", "tool_use_id": block.id, "content": str(result)}
            )
        return results

    def _setup_signal_handling(self) -> None:
        def _handler(signum: int, frame: object) -> None:
            self._shutdown = True
            self._on_shutdown()

        signal.signal(signal.SIGINT, _handler)
        signal.signal(signal.SIGTERM, _handler)

    def _on_shutdown(self) -> None:
        """Override to add cleanup logic."""

    def _log(self, event_type: str, data: dict) -> None:
        if not self.config.log_file:
            return
        entry = {
            "ts": datetime.now(timezone.utc).isoformat(),
            "event": event_type,
            "data": data,
        }
        log_path = self.config.log_file
        os.makedirs(os.path.dirname(log_path) or ".", exist_ok=True)
        with open(log_path, "a") as f:
            f.write(json.dumps(entry) + "\n")
