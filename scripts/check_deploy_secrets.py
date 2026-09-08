"""Validate required deployment secrets without printing their values."""
import os
import sys

REQUIRED = (
    "FTP_SERVER", "FTP_USERNAME", "FTP_PASSWORD", "FTP_TARGET_DIR",
    "STATS_DB_HOST", "STATS_DB_NAME", "STATS_DB_USER", "STATS_DB_PASSWORD",
    "STATS_AUTH_USER", "STATS_AUTH_PASSWORD",
)


def main():
    missing = [name for name in REQUIRED if not os.environ.get(name, "").strip()]
    if missing:
        print("Missing required deployment secrets: " + ", ".join(missing), file=sys.stderr)
        return 1
    # STATS_HASH_SALT remains optional; the existing fallback is unchanged.
    print("Required deployment secrets are present.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
