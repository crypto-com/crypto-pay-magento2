rm ${PWD##*/}.zip
zip -r ${PWD##*/}.zip . --exclude .git\* .gitignore .DS_Store publish.sh README.md