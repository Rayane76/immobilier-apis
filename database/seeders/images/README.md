# Seed Images

Place **5 property images** in this directory (JPG, JPEG, PNG, or WebP).

They will be picked randomly by `PropertySeeder` and attached to 500 sample
properties via Spatie MediaLibrary (`preservingOriginal()` — the originals are
never moved or deleted).

Suggested naming: `1.jpg`, `2.jpg`, `3.jpg`, `4.jpg`, `5.jpg`

> This directory is intentionally committed to the repository so the images are
> available inside Docker during seeding.
