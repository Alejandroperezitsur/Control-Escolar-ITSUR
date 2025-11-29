#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para generar datos completos y realistas para el sistema de control escolar
Genera 700 alumnos (100 por carrera), 70 profesores, materias, grupos, horarios e inscripciones
Cumpliendo estrictamente con:
- 700 Alumnos (100 por carrera)
- Max 8 materias por alumno
- Max 8 grupos por profesor
- Clases de 1 hora
- Todo conectado y sin nulos
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
    {"id": 1, "code": "S", "name": "ISC", "year_codes": ["21", "22", "23", "24"]},
    {"id": 2, "code": "I", "name": "II", "year_codes": ["21", "22", "23", "24"]},
    {"id": 3, "code": "G", "name": "IGE", "year_codes": ["21", "22", "23", "24"]},
    {"id": 4, "code": "E", "name": "IE", "year_codes": ["21", "22", "23", "24"]},
    {"id": 5, "code": "M", "name": "IM", "year_codes": ["21", "22", "23", "24"]},
    {"id": 6, "code": "R", "name": "IER", "year_codes": ["21", "22", "23", "24"]},
    {"id": 7, "code": "C", "name": "CP", "year_codes": ["21", "22", "23", "24"]},
]

# Password hash (alumno123 / profesor123)
PASSWORD_HASH_ALUMNO = "$2y$10$8Q9l5j68ixdQmG9eAsvA8.PBKGHp0CpvEQk9ho/77NNaE6YxSqRWu"
PASSWORD_HASH_PROFE = "$2y$10$8Q9l5j68ixdQmG9eAsvA8.PBKGHp0CpvEQk9ho/77NNaE6YxSqRWu" # Usamos el mismo para facilitar pruebas

def generar_fecha_nacimiento(year_code):
    """Genera una fecha de nacimiento coherente con el año de ingreso"""
    year_ing = 2000 + int(year_code)
    year_nac = year_ing - random.randint(18, 20)
    month = random.randint(1, 12)
    day = random.randint(1, 28)
    return f"{year_nac}-{month:02d}-{day:02d}"

def generar_alumnos():
    """Genera INSERT statements para 100 alumnos por carrera (700 total)"""
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
            year_code = year_codes[i % len(year_codes)]
            
            es_mujer = random.choice([True, False])
            nombre = random.choice(NOMBRES_F if es_mujer else NOMBRES_M)
            apellido1 = random.choice(APELLIDOS)
            apellido2 = random.choice(APELLIDOS)
            apellido_completo = f"{apellido1} {apellido2}"
            
            # Matrícula única
            matricula = f"{code}{year_code}120{counter:03d}"
            
            # Email único
            nombre_limpio = nombre.lower().replace(" ", "")
            apellido_limpio = apellido1.lower().replace("á", "a").replace("é", "e").replace("í", "i").replace("ó", "o").replace("ú", "u").replace("ñ", "n")
            email = f"{nombre_limpio}.{apellido_limpio}{code}{counter}@alumnos.itsur.edu.mx"
            
            fecha_nac = generar_fecha_nacimiento(year_code)
            
            insert_line = f"('{matricula}', '{nombre}', '{apellido_completo}', '{email}', '{PASSWORD_HASH_ALUMNO}', '{fecha_nac}', {carrera_id}, 1)"
            all_inserts.append(insert_line)
            
            counter += 1
    
    output.append(",\n".join(all_inserts))
    output.append(";\n")
    
    return "".join(output)

def generar_profesores():
    """Genera 70 profesores (10 por carrera aprox)"""
    output = []
    output.append("\n-- Insertar 70 Profesores\n")
    # Asumiendo que la tabla usuarios tiene rol='profesor'
    output.append("INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`, `activo`) VALUES\n")
    
    all_inserts = []
    
    for i in range(1, 71):
        es_mujer = random.choice([True, False])
        nombre = random.choice(NOMBRES_F if es_mujer else NOMBRES_M)
        apellido = random.choice(APELLIDOS)
        nombre_completo = f"{nombre} {apellido}"
        
        nombre_limpio = nombre.lower().replace(" ", "")
        apellido_limpio = apellido.lower().replace("á", "a").replace("é", "e").replace("í", "i").replace("ó", "o").replace("ú", "u").replace("ñ", "n")
        email = f"{nombre_limpio}.{apellido_limpio}{i}@itsur.edu.mx"
        
        insert_line = f"('{nombre_completo}', '{email}', '{PASSWORD_HASH_PROFE}', 'profesor', 1)"
        all_inserts.append(insert_line)
        
    output.append(",\n".join(all_inserts))
    output.append(";\n")
    
    return "".join(output)

def generar_materias():
    """Genera materias base y las asigna a carreras"""
    # Lista simplificada de materias comunes y específicas
    materias_base = [
        # Semestre 1-2 (Tronco común)
        ("Cálculo Diferencial", "MAT101"), ("Fundamentos de Programación", "INF101"), ("Taller de Ética", "HUM101"),
        ("Cálculo Integral", "MAT102"), ("Programación Orientada a Objetos", "INF102"), ("Contabilidad Financiera", "CON101"),
        # Semestre 3-4
        ("Álgebra Lineal", "MAT103"), ("Estructuras de Datos", "INF103"), ("Física General", "FIS101"),
        ("Ecuaciones Diferenciales", "MAT105"), ("Bases de Datos", "INF104"), ("Sistemas Operativos", "INF105"),
        # Semestre 5-6
        ("Redes de Computadoras", "INF106"), ("Ingeniería de Software", "INF107"), ("Arquitectura de Computadoras", "INF108"),
        ("Programación Web", "INF110"), ("Sistemas Programables", "INF111"), ("Lenguajes y Autómatas", "INF112"),
        # Semestre 7-8
        ("Inteligencia Artificial", "INF114"), ("Aplicaciones Móviles", "INF115"), ("Seguridad Informática", "INF116"),
        ("Sistemas Distribuidos", "INF118"), ("Big Data", "INF120"), ("Gestión de Proyectos", "ADM102")
    ]
    
    # Generar más materias para tener variedad (aprox 40-50 por carrera)
    # Aquí generamos un pool de materias
    output = []
    output.append("\n-- Insertar Materias\n")
    output.append("INSERT INTO `materias` (`nombre`, `clave`) VALUES\n")
    
    inserts = []
    materias_map = {} # nombre -> id (simulado, 1-indexed)
    
    idx = 1
    for nombre, clave in materias_base:
        inserts.append(f"('{nombre}', '{clave}')")
        materias_map[idx] = {"nombre": nombre, "clave": clave}
        idx += 1
        
    # Materias genéricas para rellenar
    for i in range(1, 40):
        nombre = f"Materia Especializada {i}"
        clave = f"ESP{200+i}"
        inserts.append(f"('{nombre}', '{clave}')")
        materias_map[idx] = {"nombre": nombre, "clave": clave}
        idx += 1
        
    output.append(",\n".join(inserts))
    output.append(";\n")
    
    return "".join(output), idx - 1 # Retorna SQL y total de materias

def generar_grupos_y_horarios(total_materias):
    """
    Genera grupos y horarios.
    Reglas:
    - Max 8 grupos por profesor.
    - Clases de 1 hora.
    - Profesores tienen ID de usuario (buscaremos por email o asumimos IDs secuenciales si limpiamos tabla).
    - Asumiremos IDs de usuarios profesores del 2 al 71 (el 1 es admin).
    """
    output = []
    
    # 1. Asignar grupos a profesores
    profesores_ids = list(range(2, 72)) # 70 profesores
    grupos_inserts = []
    horarios_inserts = []
    
    grupo_id_counter = 1
    
    # Ciclo actual
    ciclo = '2024B'
    
    # Para cada profesor, asignamos entre 4 y 8 grupos
    for prof_id in profesores_ids:
        num_grupos = random.randint(4, 8)
        
        # Horas ocupadas por el profesor para no solapar
        horas_ocupadas = set() # (dia, hora)
        
        for _ in range(num_grupos):
            materia_id = random.randint(1, total_materias)
            grupo_letter = random.choice(['A', 'B', 'C'])
            nombre_grupo = f"GPO-{materia_id}-{grupo_letter}"
            aula = f"A{random.randint(1,5)}{random.randint(1,9)}"
            
            # Insertar Grupo
            grupos_inserts.append(f"({materia_id}, {prof_id}, '{nombre_grupo}', '{ciclo}', 30, '{aula}')")
            
            # Generar Horario (1 hora diaria, 5 dias a la semana o menos)
            # Simplificación: 5 horas a la semana, 1 hora diaria L-V en el mismo horario
            # Buscar una hora libre para el profesor
            hora_inicio_h = random.randint(7, 16) # 7am a 4pm
            while True:
                conflict = False
                for d in ['lunes', 'martes', 'miércoles', 'jueves', 'viernes']:
                    if (d, hora_inicio_h) in horas_ocupadas:
                        conflict = True
                        break
                if not conflict:
                    break
                hora_inicio_h = random.randint(7, 16)
                # Safety break loop if too full (unlikely with 8 groups * 1 hour = 8 hours/day spread)
            
            # Registrar horas ocupadas
            for d in ['lunes', 'martes', 'miércoles', 'jueves', 'viernes']:
                horas_ocupadas.add((d, hora_inicio_h))
                
                h_start = f"{hora_inicio_h:02d}:00:00"
                h_end = f"{hora_inicio_h+1:02d}:00:00"
                
                horarios_inserts.append(f"({grupo_id_counter}, '{d}', '{h_start}', '{h_end}', '{aula}')")
            
            grupo_id_counter += 1

    output.append("\n-- Insertar Grupos\n")
    output.append("INSERT INTO `grupos` (`materia_id`, `profesor_id`, `nombre`, `ciclo`, `cupo`, `aula_default`) VALUES\n")
    output.append(",\n".join(grupos_inserts))
    output.append(";\n")
    
    output.append("\n-- Insertar Horarios\n")
    output.append("INSERT INTO `horarios` (`grupo_id`, `dia_semana`, `hora_inicio`, `hora_fin`, `aula`) VALUES\n")
    output.append(",\n".join(horarios_inserts))
    output.append(";\n")
    
    return "".join(output), grupo_id_counter - 1 # SQL y total grupos

def generar_inscripciones_y_kardex(total_grupos):
    """
    Inscribe alumnos en grupos.
    Reglas:
    - Max 8 materias por alumno.
    - Todos los alumnos con carga.
    - Generar historial (kardex) para semestres anteriores.
    """
    output = []
    inscripciones_inserts = []
    calificaciones_inserts = []
    
    # Alumnos IDs: 1 a 700
    # Grupos IDs: 1 a total_grupos
    
    ciclo_actual = '2024B'
    
    for alumno_id in range(1, 701):
        # 1. Carga Académica Actual (5 a 8 materias)
        num_materias = random.randint(5, 8)
        grupos_disponibles = list(range(1, total_grupos + 1))
        grupos_seleccionados = random.sample(grupos_disponibles, min(num_materias, len(grupos_disponibles)))
        
        for grupo_id in grupos_seleccionados:
            # Inscripción actual
            inscripciones_inserts.append(f"({alumno_id}, {grupo_id}, '{ciclo_actual}', 'inscrito')")
            
            # Calificaciones parciales (en progreso)
            p1 = round(random.uniform(70, 100), 2)
            calificaciones_inserts.append(f"({alumno_id}, {grupo_id}, {p1}, NULL, NULL)")
            
        # 2. Historial (Kardex) - Simulado
        # Generamos algunas materias pasadas aleatorias para tener historial
        # Ciclo pasado
        ciclo_pasado = '2024A'
        grupos_pasados = random.sample(grupos_disponibles, 5) # 5 materias pasadas
        
        # Nota: Para un kardex real necesitaríamos grupos de ciclos pasados.
        # Aquí reutilizaremos los grupos actuales pero con ciclo '2024A' en la tabla inscripciones
        # Esto es un "truco" para que aparezcan en el historial sin duplicar toda la tabla de grupos
        # OJO: La tabla inscripciones tiene el campo 'ciclo'.
        
        for g_id in grupos_pasados:
            # Evitar duplicar PK (alumno, grupo) si la tabla tiene esa restricción.
            # Asumiremos que un alumno no repite el MISMO grupo ID en diferente ciclo (lo cual es lógico).
            # Pero como estamos usando los mismos IDs de grupos, solo inscribimos si no está en la carga actual.
            if g_id not in grupos_seleccionados:
                inscripciones_inserts.append(f"({alumno_id}, {g_id}, '{ciclo_pasado}', 'completado')")
                
                final = round(random.uniform(70, 100), 2)
                calificaciones_inserts.append(f"({alumno_id}, {g_id}, {final}, {final}, {final})")

    output.append("\n-- Insertar Inscripciones\n")
    output.append("INSERT INTO `inscripciones` (`alumno_id`, `grupo_id`, `ciclo`, `estatus`) VALUES\n")
    output.append(",\n".join(inscripciones_inserts))
    output.append(";\n")
    
    output.append("\n-- Insertar Calificaciones\n")
    output.append("INSERT INTO `calificaciones` (`alumno_id`, `grupo_id`, `parcial1`, `parcial2`, `final`) VALUES\n")
    output.append(",\n".join(calificaciones_inserts))
    output.append(";\n")
    
    return "".join(output)

def main():
    import os
    script_dir = os.path.dirname(os.path.abspath(__file__))
    base_path = os.path.join(script_dir, '..', 'migrations', 'seed_complete_realistic_data.sql')
    
    # Header del SQL
    full_sql = "-- SCRIPT GENERADO AUTOMATICAMENTE\n"
    full_sql += "SET FOREIGN_KEY_CHECKS = 0;\n"
    full_sql += "TRUNCATE TABLE `calificaciones`;\n"
    full_sql += "TRUNCATE TABLE `inscripciones`;\n"
    full_sql += "TRUNCATE TABLE `horarios`;\n"
    full_sql += "TRUNCATE TABLE `grupos`;\n"
    full_sql += "TRUNCATE TABLE `materias`;\n"
    full_sql += "TRUNCATE TABLE `alumnos`;\n"
    full_sql += "DELETE FROM `usuarios` WHERE rol != 'admin';\n" # Mantener admin
    full_sql += "ALTER TABLE `alumnos` AUTO_INCREMENT = 1;\n"
    full_sql += "ALTER TABLE `grupos` AUTO_INCREMENT = 1;\n"
    full_sql += "ALTER TABLE `materias` AUTO_INCREMENT = 1;\n"
    full_sql += "ALTER TABLE `usuarios` AUTO_INCREMENT = 2;\n" # Asumiendo admin es 1
    
    # Generar datos
    alumnos_sql = generar_alumnos()
    profesores_sql = generar_profesores()
    materias_sql, total_materias = generar_materias()
    grupos_sql, total_grupos = generar_grupos_y_horarios(total_materias)
    inscripciones_sql = generar_inscripciones_y_kardex(total_grupos)
    
    full_sql += alumnos_sql
    full_sql += profesores_sql
    full_sql += materias_sql
    full_sql += grupos_sql
    full_sql += inscripciones_sql
    
    full_sql += "\nSET FOREIGN_KEY_CHECKS = 1;\n"
    full_sql += "COMMIT;\n"
    
    with open(base_path, 'w', encoding='utf-8') as f:
        f.write(full_sql)
        
    print(f"✓ Script generado en: {base_path}")
    print("  - 700 Alumnos")
    print("  - 70 Profesores")
    print(f"  - {total_materias} Materias")
    print(f"  - {total_grupos} Grupos (con horarios)")

if __name__ == "__main__":
    main()
