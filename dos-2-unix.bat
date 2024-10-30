REM On Unix you would do this: find ./ -type f -exec dos2unix {} \;
REM After installing dos2unix.exe in Windows, you can create a small bat script with the below in it to
REM recursively change the line endings. Careful if you have any hidden directories (e.g. .git)
REM
REM Make sure dos2unix is installed in c:\\bin

for /f "tokens=* delims=" %%a in ('dir "*.html" /s /b') do (
"c:\\bin\\dos2unix.exe" "%%a"
)

REM delete unwanted files
for /f "tokens=* delims=" %%b in ('dir "*.ini" /s /b') do (
del "%%b"
)