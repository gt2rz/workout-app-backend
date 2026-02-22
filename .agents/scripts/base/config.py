from __future__ import annotations

import dataclasses
import json
import os
from dataclasses import dataclass


@dataclass
class AgentConfig:
    model: str = "claude-sonnet-4-6"
    max_iterations: int = 15
    log_file: str | None = None

    @classmethod
    def from_json(cls, path: str) -> "AgentConfig":
        with open(path) as f:
            data = json.load(f)
        known = {f.name for f in dataclasses.fields(cls)}
        return cls(**{k: v for k, v in data.items() if k in known})

    @classmethod
    def from_env(cls, prefix: str = "AGENT_") -> "AgentConfig":
        return cls(
            model=os.getenv(f"{prefix}MODEL", "claude-sonnet-4-6"),
            max_iterations=int(os.getenv(f"{prefix}MAX_ITERATIONS", "15")),
            log_file=os.getenv(f"{prefix}LOG_FILE"),
        )

    def apply_overrides(self, **kwargs: object) -> None:
        """Apply non-None CLI overrides to this config in place."""
        for key, value in kwargs.items():
            if value is not None and hasattr(self, key):
                setattr(self, key, value)
