Add-Type -AssemblyName System.Drawing

$srcDir = "C:\Users\marce\.gemini\antigravity-ide\brain\51983261-0223-43c6-a8fc-2e1501880c53"
$dstDir = "c:\Users\marce\limpa frutas\assets\images"

if (!(Test-Path $dstDir)) {
    New-Item -ItemType Directory -Force -Path $dstDir
}

# Mapping files
# media__1786313200287.jpg -> Instagram screenshot
# media__1786313200310.jpg -> Spin wash cycle diagram
# media__1786313200336.jpg -> Product main dimensions diagram
# media__1786313200476.jpg -> Crank & bristles detail
# media__1786313200516.jpg -> Self-drain basket

Copy-Item "$srcDir\media__1786313200336.jpg" "$dstDir\produto-destaque.jpg" -Force
Copy-Item "$srcDir\media__1786313200310.jpg" "$dstDir\produto-funcionamento.jpg" -Force
Copy-Item "$srcDir\media__1786313200476.jpg" "$dstDir\produto-manivela.jpg" -Force
Copy-Item "$srcDir\media__1786313200516.jpg" "$dstDir\produto-drenagem.jpg" -Force
Copy-Item "$srcDir\media__1786313200287.jpg" "$dstDir\produto-uso-instagram-original.jpg" -Force

# Crop Instagram Screenshot (Remove top header and bottom footer UI overlays)
$instaPath = "$srcDir\media__1786313200287.jpg"
if (Test-Path $instaPath) {
    $img = [System.Drawing.Image]::FromFile($instaPath)
    $w = $img.Width
    $h = $img.Height

    # Crop rectangle: remove top ~16% (header UI) and bottom ~18% (footer UI)
    $cropY = [int]($h * 0.15)
    $cropH = [int]($h * 0.67)

    $rect = New-Object System.Drawing.Rectangle(0, $cropY, $w, $cropH)
    $bmp = New-Object System.Drawing.Bitmap($w, $cropH)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.DrawImage($img, (New-Object System.Drawing.Rectangle(0, 0, $w, $cropH)), $rect, [System.Drawing.GraphicsUnit]::Pixel)
    $g.Dispose()

    $bmp.Save("$dstDir\produto-uso-real.jpg", [System.Drawing.Imaging.ImageFormat]::Jpeg)
    $bmp.Dispose()
    $img.Dispose()
    Write-Host "Instagram image cropped successfully to assets/images/produto-uso-real.jpg"
}

Write-Host "Images copied successfully!"
