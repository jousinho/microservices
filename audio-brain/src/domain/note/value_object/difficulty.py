from dataclasses import dataclass


@dataclass(frozen=True)
class Difficulty:
    value: int

    def __post_init__(self) -> None:
        if self.value not in (1, 2, 3):
            raise ValueError(f"Difficulty must be 1, 2 or 3, got {self.value}")
