from dataclasses import dataclass


@dataclass(frozen=True)
class MusicalNote:
    note_id: str
    name: str
    solfege: str
    frequency: float
    octave: int

    def __post_init__(self) -> None:
        if self.frequency <= 0:
            raise ValueError(f"Frequency must be positive, got {self.frequency}")
        if self.octave not in (3, 4, 5):
            raise ValueError(f"Octave must be 3, 4 or 5, got {self.octave}")
        if not self.solfege:
            raise ValueError("Solfege cannot be empty")
