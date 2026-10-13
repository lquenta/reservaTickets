from pathlib import Path

root = Path(r"d:\LEO\DESARROLLO\nova\reservaTickets\resources")

replacements = [
    ("#e11d8a", "#ff2daa"),
    ("#E11D8A", "#FF2DAA"),
    ("#22d3ee", "#39ff14"),
    ("#22D3EE", "#39FF14"),
    ("#07040f", "#0d061b"),
    ("#07040F", "#0D061B"),
    ("#14081f", "#180a2e"),
    ("#14081F", "#180A2E"),
    ("rgba(225,29,138", "rgba(255,45,170"),
    ("rgba(225, 29, 138", "rgba(255, 45, 170"),
    ("rgba(34,211,238", "rgba(57,255,20"),
    ("rgba(34, 211, 238", "rgba(57, 255, 20"),
    ("hover:bg-fuchsia-700", "hover:bg-pink-600"),
    ("border-fuchsia-900", "border-purple-900"),
    ("border-fuchsia-800", "border-purple-800"),
    ("divide-fuchsia-900", "divide-purple-900"),
    ("hover:bg-fuchsia-950", "hover:bg-purple-950"),
    ("bg-fuchsia-950", "bg-purple-950"),
    ("hover:bg-fuchsia-900", "hover:bg-purple-900"),
    ("bg-fuchsia-900", "bg-purple-900"),
    ("border-fuchsia-400", "border-pink-400"),
    ("hover:text-fuchsia-300", "hover:text-[#a569ff]"),
    ("text-fuchsia-300", "text-[#a569ff]"),
    ("text-[#041016]", "text-[#0d061b]"),
]

skip_if_contains = [
    "@error",
    "text-red-600",
    "text-red-400",
    "border-red-500",
    "bg-red-100",
    "session('error')",
    "Sold out",
]

changed = []
for path in list(root.rglob("*.blade.php")) + list(root.rglob("*.css")) + list(root.rglob("*.js")):
    text = path.read_text(encoding="utf-8")
    orig = text
    lines = text.splitlines(keepends=True)
    out = []
    file_changed = False
    for line in lines:
        if any(m in line for m in skip_if_contains):
            # still replace brand hex on those lines if present (except error colors)
            new = line
            for a, b in replacements:
                if a.startswith("text-red") or a.startswith("border-red") or a.startswith("bg-red"):
                    continue
                if a.startswith("#") or a.startswith("rgba") or "fuchsia" in a or "041016" in a:
                    new = new.replace(a, b)
            if new != line:
                file_changed = True
            out.append(new)
            continue
        new = line
        for a, b in replacements:
            new = new.replace(a, b)
        if new != line:
            file_changed = True
        out.append(new)
    if file_changed:
        path.write_text("".join(out), encoding="utf-8")
        changed.append(str(path.relative_to(root)))

print(f"Updated {len(changed)} files")
for f in changed:
    print(f" - {f}")
