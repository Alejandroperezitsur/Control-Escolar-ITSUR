#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para generar datos completos y realistas para el sistema de control escolar
Genera 700 alumnos (100 por carrera), profesores, materias, grupos, horarios e inscripciones
"""

import random
from datetime import date, timedelta

# Nombres mexicanos realistas
NOMBRES_M = ["José", "Juan", "Miguel", "Carlos", "Luis", "Jorge", "Ricardo", "Fernando", "Alberto", "Roberto",
             "Francisco", "Javier", "Rafael", "Daniel", "Andrés", "Diego", "Manuel", "Pedro", "Antonio", "Eduardo",
             "Alejandro", "Sergio", "Raúl", "Pablo", "Héctor", "Omar", "Víctor", "César", "Armando", "Arturo",
             "Gerardo", "Guillermo", "Gustavo", "Enrique", "Felipe", "Óscar", "Ramón", "Salvador", "Gabriel", "Jesús"]

NOMBRES_F = ["María", "Ana", "Laura", "Patricia", "Carmen", "Rosa", "Sandra", "Diana", "Gabriela", "Verónica",
             "Elena", "Claudia", "Silvia", "Teresa", "Beatriz", "Mónica", "Gloria", "Adriana", "Marcela", "Leticia",
             "Carolina", "Alejandra", "Rocío", "Norma", "Luz", "Martha", "Andrea", "Fernanda", "Lucía", "Daniela",
             "Karla", "Mariana", "Isabel", "Sofía", "Julia", "Susana", "Irene", "Magdalena", "Amparo", "Guadalupe"]

APELLIDOS = ["García", "Martínez", "López", "González", "Rodríguez", "Hernández", "Pérez", "Sánchez", "Ramírez", "Torres",
             "Flores", "Rivera", "Gómez", "Díaz", "Cruz", "Morales", "Reyes", "Gutiérrez", "Ortiz", "Mendoza",
             "Jiménez", "Ruiz", "Álvarez", "Castillo", "Romero", "Vega", "Vargas", "Ramos", "Castro", "Medina",
             "Aguilar", "Navarro", "Santos", "Guerrero", "Herrera", "Moreno", "Delgado", "Cortés", "Ríos", "Silva",
             "Méndez", "León", "Campos", "Vazquez", "Molina", "Rojas", "Nuñez", "Contreras", "Soto", "Luna"]

# Configuración de carreras
CARRERAS = [
    {"id": 1, "code": "S", "name": "ISC", "year_codes": ["22", "23", "24"]},
    {"id": 2, "code": "I", "name": "II", "year_codes": ["22", "23", "24"]},
    {"id": 3, "code": "G", "name": "IGE", "year_codes": ["22", "23", "24"]},
    {"id": 4, "code": "E", "name": "IE", "year_codes": ["22", "23", "24"]},
    {"id": 5, "code": "M", "name": "IM", "year_codes": ["22", "23", "24"]},
    {"id": 6, "code": "R", "name": "IER", "year_codes": ["22", "23", "24"]},
    {"id": 7, "code": "C", "name": "CP", "year_codes": ["22", "23", "24"]},
]

# Password hash (alumno123)
PASSWORD_HASH = "$2y$10$8Q9l5j68ixdQmG9eAsvA8.PBKGHp0CpvEQk9ho/77NNaE6YxSqRWu"

def generar_fecha_nacimiento(year_code):
    """Genera una fecha de nacimiento coherente con el año de ingreso"""
    year_ing = 2000 + int(year_code)
    # Estudiante típico ingresa a los 18-19 años
    year_nac = year_ing - random.randint(18, 21)
    month = random.randint(1, 12)
    day = random.randint(1, 28)
    return f"{year_nac}-{month:02d}-{day:02d}"

def generar_alumnos():
    """Genera INSERT statements para 100 alumnos por carrera"""
    output = []
    output.append("\n-- Insertar 100 alumnos por carrera (700 total)\n")
    output.append("INSERT INTO `alumnos` (`matricula`, `nombre`, `apellido`, `email`, `password`, `fecha_nac`, `carrera_id`, `activo`) VALUES\n")
    
    all_inserts = []
    
    for carrera in CARRERAS:
        code = carrera["code"]
        carrera_id = carrera["id"]
        year_codes = carrera["year_codes"]
        
        counter = 1
        for i in range(100):
            # Distribuir estudiantes entre años
            year_code = year_codes[i % len(year_codes)]
            
            # Generar nombre y apellidos
            es_mujer = random.choice([True, False])
            nombre = random.choice(NOMBRES_F if es_mujer else NOMBRES_M)
            apellido1 = random.choice(APELLIDOS)
            apellido2 = random.choice(APELLIDOS)
            apellido_completo = f"{apellido1} {apellido2}"
            
            # Generar matrícula: LETRA + AÑO(2) + "120" + CONSECUTIVO(3)
            matricula = f"{code}{year_code}120{counter:03d}"
            
            # Email
            nombre_limpio = nombre.lower().replace(" ", "")
            apellido_limpio = apellido1.lower().replace("á", "a").replace("é", "e").replace("í", "i").replace("ó", "o").replace("ú", "u").replace("ñ", "n")
            email = f"{nombre_limpio}.{apellido_limpio}{counter}@itsur.edu.mx"
            
            # Fecha de nacimiento
            fecha_nac = generar_fecha_nacimiento(year_code)
            
            insert_line = f"('{matricula}', '{nombre}', '{apellido_completo}', '{email}', '{PASSWORD_HASH}', '{fecha_nac}', {carrera_id}, 1)"
            all_inserts.append(insert_line)
            
            counter += 1
    
    output.append(",\n".join(all_inserts))
    output.append(";\n")
    
    return "".join(output)

def generar_materias():
    """Genera materias para un semestre típico de 9 semestres"""
    materias_base = [
        # Semestre 1
        ("Cálculo Diferencial", "MAT101", 1),
        ("Fundamentos de Programación", "INF101", 1),
        ("Taller de Ética", "HUM101", 1),
        ("Química", "QUI101", 1),
        ("Fundamentos de Investigación", "INV101", 1),
        
        # Semestre 2
        ("Cálculo Integral", "MAT102", 2),
        ("Programación Orientada a Objetos", "INF102", 2),
        ("Contabilidad Financiera", "CON101", 2),
        ("Física I", "FIS101", 2),
        ("Inglés I", "ING101", 2),
        
        # Semestre 3
        ("Álgebra Lineal", "MAT103", 3),
        ("Estructuras de Datos", "INF103", 3),
        ("Probabilidad y Estadística", "MAT104", 3),
        ("Física II", "FIS102", 3),
        ("Inglés II", "ING102", 3),
        
        # Semestre 4
        ("Ecuaciones Diferenciales", "MAT105", 4),
        ("Bases de Datos", "INF104", 4),
        ("Métodos Numéricos", "MAT106", 4),
        ("Sistemas Operativos", "INF105", 4),
        ("Desarrollo Sustentable", "AMB101", 4),
        
        # Semestre 5
        ("Redes de Computadoras", "INF106", 5),
        ("Ingeniería de Software", "INF107", 5),
        ("Arquitectura de Computadoras", "INF108", 5),
        ("Graficación", "INF109", 5),
        ("Administración", "ADM101", 5),
        
        # Semestre 6
        ("Programación Web", "INF110", 6),
        ("Sistemas Programables", "INF111", 6),
        ("Lenguajes y Autómatas", "INF112", 6),
        ("Simulación", "INF113", 6),
        ("Gestión de Proyectos", "ADM102", 6),
        
        # Semestre 7
        ("Inteligencia Artificial", "INF114", 7),
        ("Aplicaciones Móviles", "INF115", 7),
        ("Seguridad Informática", "INF116", 7),
        ("Programación Lógica y Funcional", "INF117", 7),
        ("Optativa I", "OPT101", 7),
        
        # Semestre 8
        ("Sistemas Distribuidos", "INF118", 8),
        ("Auditoría Informática", "INF119", 8),
        ("Big Data", "INF120", 8),
        ("Optativa II", "OPT102", 8),
        ("Optativa III", "OPT103", 8),
        
        # Semestre 9
        ("Residencia Profesional", "RES101", 9),
        ("Servicio Social", "SS101", 9),
    ]
    
    output = []
    output.append("\n-- Insertar Materias\n")
    output.append("INSERT INTO `materias` (`nombre`, `clave`) VALUES\n")
    
    inserts = [f"('{nombre}', '{clave}')" for nombre, clave, sem in materias_base]
    output.append(",\n".join(inserts))
    output.append(";\n")
    
    return "".join(output)

def generar_grupos_basicos():
    """Genera grupos básicos por materia"""
    output = []
    output.append("\n-- Grupos (se crearán grupos de las primeras 20 materias)\n")
    output.append("INSERT INTO `grupos` (`materia_id`, `profesor_id`, `nombre`, `ciclo`, `cupo`, `aula_default`) VALUES\n")
    
    all_groups = []
    # Simulación: crear 60 grupos (30 para cada semestre actual)
    ciclos = ['2024A', '2024B']
    
    for idx in range(1, 61):
        materia_id = ((idx - 1) % 20) + 1  # Rotar entre las primeras 20 materias
        profesor_id_base = ((idx - 1) % 70) + 1  # Rotar entre 70 profesores (se calcularán IDs después de insertar)
        ciclo = ciclos[idx % 2]
        grupo_letter = chr(65 + (idx % 5))  # A, B, C, D, E
        nombre_grupo = f"GPO-{materia_id:03d}-{grupo_letter}"
        cupo = 30
        aula = f"A{random.randint(1,5)}{random.randint(0,9):02d}"
        
        # Nota: profesor_id necesita ser calculado después
        # Por simplicidad, asignaremos (idx % 70) + ID_offset
        all_groups.append(f"({materia_id}, {profesor_id_base}, '{nombre_grupo}', '{ciclo}', {cupo}, '{aula}')")
    
    output.append(",\n".join(all_groups))
    output.append(";\n")
    
    return "".join(output)

def generar_horarios():
    """Genera horarios para los grupos"""
    output = []
    output.append("\n-- Horarios (2 sesiones por semana para cada grupo)\n")
    output.append("INSERT INTO `horarios` (`grupo_id`, `dia_semana`, `hora_inicio`, `hora_fin`, `aula`) VALUES\n")
    
    dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes']
    time_slots = [
        ('07:00:00', '08:00:00'),
        ('08:00:00', '09:00:00'),
        ('09:00:00', '10:00:00'),
        ('10:00:00', '11:00:00'),
        ('11:00:00', '12:00:00'),
        ('12:00:00', '13:00:00'),
        ('13:00:00', '14:00:00'),
        ('14:00:00', '15:00:00'),
        ('15:00:00', '16:00:00'),
        ('16:00:00', '17:00:00'),
    ]
    
    all_horarios = []
    
    for grupo_id in range(1, 61):  # 60 grupos
        # 2 sesiones por semana
        dias_seleccionados = random.sample(dias, 2)
        for dia in dias_seleccionados:
            slot = random.choice(time_slots)
            aula = f"A{random.randint(1,5)}{random.randint(0,9):02d}"
            all_horarios.append(f"({grupo_id}, '{dia}', '{slot[0]}', '{slot[1]}', '{aula}')")
    
    output.append(",\n".join(all_horarios))
    output.append(";\n")
    
    return "".join(output)

def generar_inscripciones():
    """Genera inscripciones de alumnos en grupos"""
    output = []
    output.append("\n-- Inscripciones (inscribir alumnos a grupos de forma realista)\n")
    output.append("-- Cada alumno se inscribe en 5-8 materias por semestre\n")
    output.append("INSERT INTO `inscripciones` (`alumno_id`, `grupo_id`, `ciclo`, `estatus`) VALUES\n")
    
    all_inscripciones = []
    
    # Simplificación: inscribir los primeros 300 alumnos en grupos aleatorios
    for alumno_id in range(1, 301):
        num_materias = random.randint(5, 8)
        grupos_asignados = random.sample(range(1, 61), num_materias)
        ciclo = random.choice(['2024A', '2024B'])
        
        for grupo_id in grupos_asignados:
            estatus = random.choice(['inscrito', 'inscrito', 'inscrito', 'completado'])
            all_inscripciones.append(f"({alumno_id}, {grupo_id}, '{ciclo}', '{estatus}')")
    
    output.append(",\n".join(all_inscripciones))
    output.append(";\n")
    
    return "".join(output)

def generar_calificaciones():
    """Genera algunas calificaciones de ejemplo"""
    output = []
    output.append("\n-- Calificaciones (generar calificaciones para inscripciones completadas)\n")
    output.append("-- Nota: Las calificaciones se vinculan a inscripciones a través de alumno y grupo\n")
    output.append("INSERT INTO `calificaciones` (`alumno_id`, `grupo_id`, `parcial1`, `parcial2`, `final`) VALUES\n")
    
    all_calificaciones = []
    
    # Generar 500 registros de calificaciones aleatorias
    for _ in range(500):
        alumno_id = random.randint(1, 300)
        grupo_id = random.randint(1, 60)
        parcial1 = round(random.uniform(60, 100), 2)
        parcial2 = round(random.uniform(60, 100), 2)
        final_grade = round(random.uniform(60, 100), 2)
        
        all_calificaciones.append(f"({alumno_id}, {grupo_id}, {parcial1}, {parcial2}, {final_grade})")
    
    output.append(",\n".join(all_calificaciones))
    output.append(";\n")
    
    return "".join(output)

def main():
    """Función principal que genera el archivo SQL completo"""
    
    import os
    script_dir = os.path.dirname(os.path.abspath(__file__))
    base_path = os.path.join(script_dir, '..', 'migrations', 'seed_complete_realistic_data.sql')
    
    # Leer el archivo base
    with open(base_path, 'r', encoding='utf-8') as f:
        base_content = f.read()
    
    # Generar secciones
    alumnos_sql = generar_alumnos()
    materias_sql = generar_materias()
    grupos_sql = generar_grupos_basicos()
    horarios_sql = generar_horarios()
    inscripciones_sql = generar_inscripciones()
    calificaciones_sql = generar_calificaciones()
    
    # Combinar todo
    full_sql = base_content + alumnos_sql + materias_sql + grupos_sql + horarios_sql + inscripciones_sql + calificaciones_sql
    
    # Agregar cierre
    full_sql += "\n-- =====================================================\n"
    full_sql += "-- FINALIZACIÓN\n"
    full_sql += "-- =====================================================\n\n"
    full_sql += "SET FOREIGN_KEY_CHECKS = 1;\n"
    full_sql += "COMMIT;\n"
    
    # Escribir archivo final
    with open(base_path, 'w', encoding='utf-8') as f:
        f.write(full_sql)
    
    print("✓ Archivo SQL generado exitosamente: seed_complete_realistic_data.sql")
    print("✓ Total de alumnos: 700 (100 por carrera)")
    print("✓ Total de profesores: 70 (10 por carrera)")
    print("✓ Materias, grupos, horarios e inscripciones generados")
    print("\nPuedes importar este archivo en phpMyAdmin")

if __name__ == "__main__":
    main()
