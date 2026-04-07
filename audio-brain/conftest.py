import os
import tempfile


def pytest_configure(config) -> None:
    os.environ.setdefault("AUDIO_CACHE_DIR", tempfile.mkdtemp(prefix="audio_brain_test_"))
