from pathlib import Path
import re

root = Path(r"d:\LEO\DESARROLLO\nova\reservaTickets\resources")

hex_map = {
    "#e50914": "#e11d8a",
    "#E50914": "#E11D8A",
    "#1a0505": "#14081f",
    "#1A0505": "#14081F",
}

class_map = [
    (r"border-red-900", "border-fuchsia-900"),
    (r"border-red-800", "border-fuchsia-800"),
    (r"divide-red-900", "divide-fuchsia-900"),
    (r"hover:bg-red-600", "hover:bg-fuchsia-700"),
    (r"hover:text-red-400", "hover:text-fuchsia-300"),
    (r"hover:bg-red-950", "hover:bg-fuchsia-950"),
    (r"bg-red-950", "bg-fuchsia-950"),
    (r"ring-red-900", "ring-fuchsia-900"),
    (r"from-red-900", "from-fuchsia-900"),
    (r"to-red-900", "to-fuchsia-900"),
    (r"via-red-900", "via-fuchsia-900"),
]


def should_keep_red_line(line: str) -> bool:
    if "@error" in line:
        return True
    if "text-sm text-red-400" in line and ("$message" in line or "{{ $message }}" in line):
        return True
    if "text-red-600" in line and ("$message" in line or "dark:text-red-400" in line):
        return True
    if "border-2 border-red-500" in line or "bg-red-900/30" in line or "text-red-200" in line:
        return True
    if "bg-red-100" in line or "text-red-800" in line or "dark:text-red-200" in line:
        return True
    if "bg-red-600 hover:bg-red-700" in line:  # report PDF danger-ish
        return True
    if "Fallido" in line:
        return True
    return False


changed_files = []
for path in root.rglob("*"):
    if not path.is_file():
        continue
    if path.suffix not in {".css", ".js"} and not str(path).endswith(".blade.php"):
        continue

    text = path.read_text(encoding="utf-8")
    orig = text

    for a, b in hex_map.items():
        text = text.replace(a, b)

    for a, b in class_map:
        text = re.sub(a, b, text)

    lines = text.splitlines(keepends=True)
    new_lines = []
    for line in lines:
        if should_keep_red_line(line):
            new_lines.append(line)
            continue
        line2 = line
        line2 = re.sub(r"\bbg-red-700\b", "bg-fuchsia-700", line2)
        line2 = re.sub(r"\btext-red-400\b", "text-fuchsia-300", line2)
        line2 = re.sub(r"border-red-500/40", "border-fuchsia-500/40", line2)
        new_lines.append(line2)
    text = "".join(new_lines)

    if text != orig:
        path.write_text(text, encoding="utf-8")
        changed_files.append(str(path.relative_to(root)))

print(f"Updated {len(changed_files)} files")
for f in sorted(changed_files):
    print(f" - {f}")
