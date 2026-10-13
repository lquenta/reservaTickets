from pathlib import Path

root = Path(r"d:\LEO\DESARROLLO\nova\reservaTickets\resources")

# Focus rings → cyan accent (keep magenta for filled brand controls)
replacements = [
    ("focus:ring-[#e11d8a]", "focus:ring-[#22d3ee]"),
    ("focus:border-[#e11d8a]", "focus:border-[#22d3ee]"),
    ("focus:ring-1 focus:ring-[#e11d8a]", "focus:ring-1 focus:ring-[#22d3ee]"),
    ("hover:text-fuchsia-300", "hover:text-[#22d3ee]"),
    ("text-fuchsia-300 uppercase tracking-wider", "text-[#22d3ee] uppercase tracking-wider"),
]

# Skip lines where focus ring is on brand radio/checkbox text color pairing only if we want magenta radios
# Actually proposal says cyan for focus - radios with text-[#e11d8a] focus:ring should get cyan ring too - OK

changed = []
for path in root.rglob("*.blade.php"):
    text = path.read_text(encoding="utf-8")
    orig = text
    for a, b in replacements:
        text = text.replace(a, b)
    if text != orig:
        path.write_text(text, encoding="utf-8")
        changed.append(str(path.relative_to(root)))

print(f"Updated {len(changed)} files")
for f in changed:
    print(f" - {f}")
