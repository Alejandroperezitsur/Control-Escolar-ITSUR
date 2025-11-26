import re

file_path = r'c:\xampp\htdocs\PWBII\Control-Escolar-ITSUR\migrations\seed_control_escolar_data.sql'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace column definition
content = content.replace(
    'INSERT IGNORE INTO calificaciones (alumno_id, grupo_id, calificacion, final)',
    'INSERT IGNORE INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final)'
)

# Replace values: (..., grade, 1) -> (..., grade, grade, grade)
# We look for a comma, whitespace, digits, whitespace, comma, whitespace, 1, closing paren
# We capture the digits (grade)
content = re.sub(r',\s*(\d+)\s*,\s*1\)', r', \1, \1, \1)', content)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("File updated successfully.")
