"""Validate the encoding contract for Bearsampp language files."""

from pathlib import Path
import sys


REPO_ROOT = Path(__file__).resolve().parents[2]
LANG_DIR = REPO_ROOT / "core" / "langs"
UTF8_BOM = b"\xef\xbb\xbf"


def validate_file(path):
    issues = []

    try:
        content = path.read_bytes()
    except OSError as error:
        return ["could not read file: {}".format(error)]

    if content.startswith(UTF8_BOM):
        issues.append("UTF-8 BOM is forbidden")

    try:
        content.decode("utf-8", errors="strict")
    except UnicodeDecodeError as error:
        issues.append(
            "invalid UTF-8 at byte offset {} ({})".format(error.start, error.reason)
        )

    return issues


def main():
    files = sorted(LANG_DIR.glob("*.lang"))
    if not files:
        print("No .lang files found in {}".format(LANG_DIR), file=sys.stderr)
        return 1

    failed = False
    for path in files:
        for issue in validate_file(path):
            print("{}: {}".format(path.relative_to(REPO_ROOT), issue), file=sys.stderr)
            failed = True

    if failed:
        return 1

    print("Validated {} language files as UTF-8 without BOM.".format(len(files)))
    return 0


if __name__ == "__main__":
    sys.exit(main())
