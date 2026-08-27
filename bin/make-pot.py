#!/usr/bin/env python3
"""Generate a deterministic POT file without requiring WP-CLI.

This lightweight extractor covers the gettext calls used by this repository.
For a final WordPress.org release, `wp i18n make-pot` may also be run as an
independent verification step.
"""

from __future__ import annotations

import ast
import datetime as dt
import json
import re
from dataclasses import dataclass, field
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DOMAIN = "lebanon-commerce-toolkit"
OUTPUT = ROOT / "languages" / f"{DOMAIN}.pot"
FUNCTIONS = (
    "esc_html__",
    "esc_html_e",
    "esc_attr__",
    "esc_attr_e",
    "__",
    "_e",
    "_x",
)


@dataclass(order=True)
class Message:
    msgid: str
    context: str = ""
    references: set[str] = field(default_factory=set, compare=False)


def decode_literal(quote: str, body: str) -> str:
    try:
        return ast.literal_eval(f"{quote}{body}{quote}")
    except (SyntaxError, ValueError):
        return body.replace("\\'", "'").replace('\\"', '"').replace("\\\\", "\\")


def po_quote(value: str) -> str:
    escaped = (
        value.replace("\\", "\\\\")
        .replace('"', '\\"')
        .replace("\t", "\\t")
        .replace("\r", "\\r")
        .replace("\n", "\\n")
    )
    return f'"{escaped}"'


def scan_text(path: Path, messages: dict[tuple[str, str], Message]) -> None:
    text = path.read_text(encoding="utf-8")
    rel = path.relative_to(ROOT).as_posix()

    for function in FUNCTIONS:
        first_arg = re.compile(
            rf"\b{re.escape(function)}\s*\(\s*(?P<q>['\"])(?P<body>(?:\\.|(?!\1).)*?)\1",
            re.DOTALL,
        )
        for match in first_arg.finditer(text):
            msgid = decode_literal(match.group("q"), match.group("body"))
            if not msgid:
                continue

            context = ""
            if function == "_x":
                remainder = text[match.end() : match.end() + 1000]
                context_match = re.match(
                    r"\s*,\s*(?P<q>['\"])(?P<body>(?:\\.|(?!\1).)*?)\1",
                    remainder,
                    re.DOTALL,
                )
                if context_match:
                    context = decode_literal(context_match.group("q"), context_match.group("body"))

            line = text.count("\n", 0, match.start()) + 1
            key = (context, msgid)
            messages.setdefault(key, Message(msgid=msgid, context=context)).references.add(f"{rel}:{line}")


def scan_block_json(path: Path, messages: dict[tuple[str, str], Message]) -> None:
    data = json.loads(path.read_text(encoding="utf-8"))
    if data.get("textdomain") != DOMAIN:
        return

    rel = path.relative_to(ROOT).as_posix()
    for key in ("title", "description"):
        value = data.get(key)
        if isinstance(value, str) and value:
            message_key = ("", value)
            messages.setdefault(message_key, Message(msgid=value)).references.add(rel)


def main() -> None:
    messages: dict[tuple[str, str], Message] = {}

    for path in sorted(ROOT.rglob("*.php")) + sorted(ROOT.rglob("*.js")):
        if any(part in {"vendor", "node_modules", "dist"} for part in path.parts):
            continue
        scan_text(path, messages)

    for path in sorted((ROOT / "blocks").glob("*/block.json")):
        scan_block_json(path, messages)

    for msgid, reference in (
        ("Lebanon Commerce Toolkit for WooCommerce", "lebanon-commerce-toolkit.php"),
        (
            "Lebanese checkout locations, phone normalization, secondary currency display, and district-based shipping for WooCommerce.",
            "lebanon-commerce-toolkit.php",
        ),
    ):
        messages.setdefault(("", msgid), Message(msgid=msgid)).references.add(reference)

    version_match = re.search(
        r"^ \* Version:\s+([^\s]+)",
        (ROOT / "lebanon-commerce-toolkit.php").read_text(encoding="utf-8"),
        re.MULTILINE,
    )
    version = version_match.group(1) if version_match else "0.1.0"
    creation = dt.datetime.now(dt.timezone.utc).strftime("%Y-%m-%d %H:%M+0000")

    lines = [
        "# Copyright (C) 2026 Pro-Solutions.net",
        "# This file is distributed under the GPL-2.0-or-later license.",
        'msgid ""',
        'msgstr ""',
        po_quote(f"Project-Id-Version: Lebanon Commerce Toolkit for WooCommerce {version}\n"),
        po_quote("Report-Msgid-Bugs-To: https://pro-solutions.net/contact/\n"),
        po_quote(f"POT-Creation-Date: {creation}\n"),
        po_quote("MIME-Version: 1.0\n"),
        po_quote("Content-Type: text/plain; charset=UTF-8\n"),
        po_quote("Content-Transfer-Encoding: 8bit\n"),
        po_quote(f"X-Domain: {DOMAIN}\n"),
        "",
    ]

    for message in sorted(messages.values(), key=lambda item: (item.msgid.casefold(), item.context.casefold())):
        refs = " ".join(sorted(message.references))
        if refs:
            lines.append(f"#: {refs}")
        if message.context:
            lines.append(f"msgctxt {po_quote(message.context)}")
        lines.append(f"msgid {po_quote(message.msgid)}")
        lines.append('msgstr ""')
        lines.append("")

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT.write_text("\n".join(lines), encoding="utf-8")
    print(f"Generated {OUTPUT.relative_to(ROOT)} with {len(messages)} messages.")


if __name__ == "__main__":
    main()
