FOR %%f IN (*.svg) DO magick -size 4096x4096 "%%f" -trim -background transparent -gravity center -resize 500x500 "%%f.png"

