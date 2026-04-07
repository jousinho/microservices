import pytest

from src.domain.note.value_object.difficulty import Difficulty


def test_difficulty__with_value_1__should_be_valid():
    assert Difficulty(1).value == 1


def test_difficulty__with_value_2__should_be_valid():
    assert Difficulty(2).value == 2


def test_difficulty__with_value_3__should_be_valid():
    assert Difficulty(3).value == 3


def test_difficulty__with_value_above_3__should_raise_error():
    with pytest.raises(ValueError, match="Difficulty must be 1, 2 or 3"):
        Difficulty(4)


def test_difficulty__with_value_below_1__should_raise_error():
    with pytest.raises(ValueError, match="Difficulty must be 1, 2 or 3"):
        Difficulty(0)
