from pathlib import Path

root = Path(r"d:\LEO\DESARROLLO\nova\reservaTickets\resources")

replacements = [
    ("rgba(229,9,20", "rgba(225,29,138"),
    ("rgba(229, 9, 20", "rgba(225, 29, 138"),
    ("hover:bg-red-900/30", "hover:bg-fuchsia-900/30"),
    ("hover:bg-red-900/20", "hover:bg-fuchsia-900/20"),
    ("bg-red-900/50", "bg-fuchsia-900/50"),
    ("bg-red-900/30", "bg-fuchsia-900/30"),
    ("bg-red-900/20", "bg-fuchsia-900/20"),
    ("bg-red-900/10", "bg-fuchsia-900/10"),
    ("border-red-400/50", "border-fuchsia-400/50"),
    ("hover:bg-red-600", "hover:bg-fuchsia-700"),
    ("hover:text-red-400", "hover:text-fuchsia-300"),
]

# Keep semantic danger/error reds on these lines
skip_if_contains = [
    "Sold out",
    "Fallido",
    'role="alert"',
    "border-2 border-red-500",
    "session('error')",
    "text-red-200",
    "remove-section",
    "layout-btn-delete",
    "bg-red-100",
    "bg-red-50",
    "border-red-300",
    "border-red-700",
    "hover:bg-red-100",
    "hover:bg-red-50",
    "text-red-600",
    "text-red-700",
    "text-red-800",
    "dark:text-red-300",
    "dark:text-red-400",
    "@error",
]

changed = []
for path in list(root.rglob("*.blade.php")) + list(root.rglob("*.css")) + list(root.rglob("*.js")):
    text = path.read_text(encoding="utf-8")
    lines = text.splitlines(keepends=True)
    out = []
    file_changed = False
    for line in lines:
        if any(m in line for m in skip_if_contains):
            out.append(line)
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
