# Download a neutral corporate login background (Unsplash) to public/images/login-hero.jpg
# Photo: modern office towers — brand-neutral, not tied to a specific insurer.
$url = "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1920&q=85"
$out = Join-Path $PSScriptRoot "public\images\login-hero.jpg"

try {
    Invoke-WebRequest -Uri $url -OutFile $out -UseBasicParsing
    Write-Host "Downloaded login background to $out"
} catch {
    Write-Host "Error: $_"
    exit 1
}
