FOR %%f IN (*.svg) DO magick -size 4096x4096 -background transparent "%%f" -trim -gravity center -resize 500x500 "%%~nf.png"

