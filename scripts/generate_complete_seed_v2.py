#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para generar datos COMPLETOS y COHERENTES para control escolar
Versión 2 - Con todas las correcciones solicitadas

GENERA:
- 700 alumnos (100 por carrera) - con car

era asignada
- 70 profesores - CON carrera_id asignada
- Materias - CON créditos (4-6), num_parciales (2-5), tipo
- Grupos - PARA TODAS las materias
- Relaciones materias_carrera - COMPLETAS
- Calificaciones - CON parciales variables según materia
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

# Carreras (7)
CARRERAS = [
    {"id": 1, "code": "S", "name": "ISC", "year_codes": ["21", "22", "23", "24"]},
    {"id": 2, "code": "I", "name": "II", "year_codes": ["21", "22", "23", "24"]},
    {"id": 3, "code": "G", "name": "IGE", "year_codes": ["21", "22", "23", "24"]},
    {"id": 4, "code": "E", "name": "IE", "year_codes": ["21", "22", "23", "24"]},
    {"id": 5, "code": "M", "name": "IM", "year_codes": ["21", "22", "23", "24"]},
    {"id": 6, "code": "R", "name": "IER", "year_codes": ["21", "22", "23", "24"]},
    {"id": 7, "code": "C", "name": "CP", "year_codes": ["21", "22", "23", "24"]},
]

# Materias con detalles completos
MATERIAS_COMPLETAS = [
    # ISC (Sistemas) - Semestre 1-8
    ("Cálculo Diferencial", "ISC-1001", "ISC", 5, 2, "Básica"),
    ("Fundamentos de Programación", "ISC-1002", "ISC", 6, 3, "Básica"),
    ("Taller de Ética", "HUM-1001", "ISC", 4, 2, "Básica"),
    ("Cálculo Integral", "ISC-1003", "ISC", 5, 2, "Básica"),
    ("Programación Orientada a Objetos", "ISC-1004", "ISC", 6, 3, "Básica"),
    ("Álgebra Lineal", "ISC-2001", "ISC", 5, 2, "Básica"),
    ("Estructuras de Datos", "ISC-2002", "ISC", 6, 3, "Básica"),
    ("Bases de Datos", "ISC-3001", "ISC", 6, 4, "Especialidad"),
    ("Redes de Computadoras", "ISC-3002", "ISC", 5, 3, "Especialidad"),
    ("Ingeniería de Software", "ISC-3003", "ISC", 6, 4, "Especialidad"),
    ("Inteligencia Artificial", "ISC-4001", "ISC", 5, 3, "Especialidad"),
    ("Seguridad Informática", "ISC-4002", "ISC", 5, 3, "Especialidad"),
    
    # II (Industrial) - Semestre 1-8
    ("Química General", "II-1001", "II", 5, 2, "Básica"),
    ("Dibujo Técnico", "II-1002", "II", 4, 2, "Básica"),
    ("Estadística", "II-2001", "II", 5, 3, "Básica"),
    ("Control de Calidad", "II-3001", "II", 5, 3, "Especialidad"),
    ("Ingeniería de Procesos", "II-3002", "II", 6, 4, "Especialidad"),
    ("Simulación de Sistemas", "II-4001", "II", 5, 3, "Especialidad"),
    
    # IGE (Gestión) - Semestre 1-8
    ("Introducción a la Gestión", "G-1001", "IGE", 4, 2, "Básica"),
    ("Economía", "G-2001", "IGE", 5, 3, "Básica"),
    ("Finanzas Corporativas", "G-3001", "IGE", 6, 4, "Especialidad"),
    ("Gestión de la Cadena de Suministro", "G-4001", "IGE", 5, 3, "Especialidad"),
    
    # IE (Electrónica) - Semestre 1-8
    ("Circuitos Eléctricos", "E-1001", "IE", 6, 3, "Básica"),
    ("Electrónica Analógica", "E-2001", "IE", 6, 3, "Básica"),
    ("Sistemas Digitales", "E-3001", "IE", 6, 4, "Especialidad"),
    ("Microcontroladores", "E-4001", "IE", 6, 4, "Especialidad"),
    
    # IM (Mecatrónica) - Semestre 1-8
    ("Mecánica", "M-1001", "IM", 6, 3, "Básica"),
    ("Robótica", "M-3001", "IM", 6, 4, "Especialidad"),
    ("Automatización", "M-4001", "IM", 6, 4, "Especialidad"),
    
    # IER (Energías Renovables) - Semestre 1-8
    ("Energía Solar", "R-2001", "IER", 5, 3, "Básica"),
    ("Energía Eólica", "R-3001", "IER", 5, 3, "Especialidad"),
    ("Sistemas Fotovoltaicos", "R-4001", "IER", 6, 4, "Especialidad"),
    
    # CP (Contador) - Semestre 1-8
    ("Contabilidad Básica", "CP-1001", "CP", 5, 2, "Básica"),
    ("Contabilidad Financiera", "CP-2001", "CP", 5, 3, "Básica"),
    ("Auditoría", "CP-3001", "CP", 6, 4, "Especialidad"),
    ("Fiscal", "CP-4001", "CP", 6, 4, "Especialidad"),
]

PASSWORD_HASH = "$2y$10$8Q9l5j68ixdQmG9eAsvA8.PBKGHp0CpvEQk9ho/77NNaE6YxSqRWu"

def generarAlumnos():
    """700 alumnos (100 por carrera)"""
    output = ["\\n-- ALUMNOS (700 - 100 por carrera)\\n"]
    output.append("INSERT INTO `alumnos` (`matricula`, `nombre`, `apellido`, `email`, `password`, `fecha_nac`, `carrera_id`, `activo`) VALUES\\n")
    
    inserts = []
    for carrera in CARRERAS:
        code, cid = carrera["code"], carrera["id"]
        for i in range(1, 101):
            es_mujer = random.choice([True, False])
            nombre = random.choice(NOMBRES_F if es_mujer else NOMBRES_M)
            apellido = f"{random.choice(APELLIDOS)} {random.choice(APELLIDOS)}"
            
            year_code = random.choice(carrera["year_codes"])
            matricula = f"{code}{year_code}120{i:03d}"
            
            nombre_limpio = nombre.lower().replace(" ", "")
            ap_limpio = apellido.split()[0].lower()[:8]
            email = f"{nombre_limpio}.{ap_limpio}{code}{i}@alumnos.itsur.edu.mx"
            
            year_nac = 2000 + int(year_code) - random.randint(18, 21)
            fecha_nac = f"{year_nac}-{random.randint(1,12):02d}-{random.randint(1,28):02d}"
            
            inserts.append(f"('{matricula}', '{nombre}', '{apellido}', '{email}', '{PASSWORD_HASH}', '{fecha_nac}', {cid}, 1)")
    
    output.append(",\\n".join(inserts))
    output.append(";\\n")
    return "".join(output)

def generarProfesores():
    """70 profesores CON carrera_id"""
    output = ["\\n-- PROFESORES (70 con carrera asignada)\\n"]
    output.append("INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`, `activo`, `carrera_id`) VALUES\\n")
    
    inserts = []
    for i in range(1, 71):
        es_mujer = random.choice([True, False])
        nombre = random.choice(NOMBRES_F if es_mujer else NOMBRES_M)
        apellido = random.choice(APELLIDOS)
        nombre_completo = f"{nombre} {apellido}"
        
        email = f"{nombre.lower()}.{apellido.lower()}{i}@itsur.edu.mx"
        carrera_id = random.randint(1, 7)  # ASIGNAR CARRERA ALEATORIA
        
        inserts.append(f"('{nombre_completo}', '{email}', '{PASSWORD_HASH}', 'profesor', 1, {carrera_id})")
    
    output.append(",\\n".join(inserts))
    output.append(";\\n")
    return "".join(output)

def generarMaterias():
    """Materias CON créditos, tipo, num_parciales"""
    output = ["\\n-- MATERIAS (con créditos, tipo, num_parciales)\\n"]
    output.append("INSERT INTO `materias` (`nombre`, `clave`, `creditos`, `num_parciales`, `tipo`) VALUES\\n")
    
    inserts = []
    for nombre, clave, _, creditos, parciales, tipo in MATERIAS_COMPLETAS:
        inserts.append(f"('{nombre}', '{clave}', {creditos}, {parciales}, '{tipo}')")
    
    output.append(",\\n".join(inserts))
    output.append(";\\n")
    return "".join(output)

def generarGrupos():
    """Grupos - UNO POR MATERIA mínimo"""
    output = ["\\n-- GRUPOS (uno por materia con profesor)\\n"]
    output.append("INSERT INTO `grupos` (`materia_id`, `profesor_id`, `nombre`, `ciclo`, `cupo`, `aula_default`) VALUES\\n")
    
    inserts = []
    materia_id = 1
    for nombre, clave, carrera_code, _, _, _ in MATERIAS_COMPLETAS:
        profesor_id = random.randint(2, 71)  # Profesores van del 2 al 71 (el 1 es admin)
        grupo_nombre = f"{clave.split('-')[1]}-A"
        ciclo = random.choice(["2024-1", "2024-2"])
        aula = f"{random.choice(['A','B','C'])}{random.randint(1,5)}{random.randint(1,5)}"
        
        inserts.append(f"({materia_id}, {profesor_id}, '{grupo_nombre}', '{ciclo}', 30, '{aula}')")
        materia_id += 1
    
    output.append(",\\n".join(inserts))
    output.append(";\\n")
    return "".join(output)

def generarMateriasCarrera():
    """Relaciones materias_carrera COMPLETAS"""
    output = ["\\n-- MATERIAS_CARRERA (relaciones completas)\\n"]
    output.append("INSERT INTO `materias_carrera` (`materia_id`, `carrera_id`, `semestre`, `tipo`, `creditos`) VALUES\\n")
    
    inserts = []
    materia_id = 1
    carrera_map = {"ISC": 1, "II": 2, "IGE": 3, "IE": 4, "IM": 5, "IER": 6, "CP": 7}
    
    for nombre, clave, carrera_code, creditos, _, tipo in MATERIAS_COMPLETAS:
        carrera_id = carrera_map[carrera_code]
        semestre = random.randint(1, 8)
        
        inserts.append(f"({materia_id}, {carrera_id}, {semestre}, '{tipo}', {creditos})")
        materia_id += 1
    
    output.append(",\\n".join(inserts))
    output.append(";\\n")
    return "".join(output)

def generarCalificaciones():
    """Calificaciones con parciales VARIABLES"""
    output = ["\\n-- CALIFICACIONES (parciales variables 2-5)\\n"]
    output.append("INSERT INTO `calificaciones` (`alumno_id`, `grupo_id`, `parcial1`, `parcial2`, `parcial3`, `parcial4`, `parcial5`, `final`) VALUES\\n")
    
    inserts = []
    # Dar 5-8 calificaciones por alumno
    for alumno_id in range(1, 701):
        num_materias = random.randint(5, 8)
        grupos = random.sample(range(1, len(MATERIAS_COMPLETAS) + 1), num_materias)
        
        for grupo_id in grupos:
            # Obtener num_parciales de la materia
            materia_info = MATERIAS_COMPLETAS[grupo_id - 1]
            num_parciales = materia_info[4]  # índice 4 es num_parciales
            
            # Generar calificaciones según num_parciales
            p1 = random.randint(70, 100)
            p2 = random.randint(70, 100)
            p3 = random.randint(70, 100) if num_parciales >= 3 else "NULL"
            p4 = random.randint(70, 100) if num_parciales >= 4 else "NULL"
            p5 = random.randint(70, 100) if num_parciales >= 5 else "NULL"
            
            # Calcular promedio según parciales activos
            if num_parciales == 2:
                promedio = (p1 + p2) / 2
            elif num_parciales == 3:
                promedio = (p1 + p2 + p3) / 3
            elif num_parciales == 4:
                promedio = (p1 + p2 + p3 + p4) / 4
            else:  # 5
                promedio = (p1 + p2 + p3 + p4 + p5) / 5
            
            final = round(promedio, 2)
            
            inserts.append(f"({alumno_id}, {grupo_id}, {p1}, {p2}, {p3}, {p4}, {p5}, {final})")
    
    output.append(",\\n".join(inserts))
    output.append(";\\n")
    return "".join(output)

def main():
    """Generar SQL completo"""
    output = []
    output.append("-- SCRIPT GENERADO AUTOMATICAMENTE V2\\n")
    output.append("-- Datos COMPLETOS y COHERENTES\\n")
    output.append("SET FOREIGN_KEY_CHECKS = 0;\\n")
    output.append("DELETE FROM `calificaciones`;\\n")
    output.append("DELETE FROM `inscripciones`;\\n")
    output.append("DELETE FROM `horarios`;\\n")
    output.append("DELETE FROM `grupos`;\\n")
    output.append("DELETE FROM `materias_carrera`;\\n")
    output.append("DELETE FROM `materias`;\\n")
    output.append("DELETE FROM `alumnos`;\\n")
    output.append("DELETE FROM `usuarios` WHERE rol != 'admin';\\n")
    output.append("ALTER TABLE `alumnos` AUTO_INCREMENT = 1;\\n")
    output.append("ALTER TABLE `grupos` AUTO_INCREMENT = 1;\\n")
    output.append("ALTER TABLE `materias` AUTO_INCREMENT = 1;\\n")
    output.append("ALTER TABLE `usuario` AUTO_INCREMENT = 2;\\n")
    output.append("SET FOREIGN_KEY_CHECKS = 1;\\n")
    
    output.append(generarAlumnos())
    output.append(generarProfesores())
    output.append(generarMaterias())
    output.append(generarGrupos())
    output.append(generarMateriasCarrera())
    output.append(generarCalificaciones())
    
    # Guardar
    import os
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    output_f = os.path.join(base_dir, "migrations", "seed_complete_v2.sql")
    with open(output_f, "w", encoding="utf-8") as f:
        f.write("".join(output))
    
    print("✓ Generado: migrations/seed_complete_v2.sql")
    print("  - 700 alumnos (100 por carrera)")
    print("  - 70 profesores (CON carrera_id)")
    print("  - Materias CON créditos/tipo/parciales")
    print("  - Grupos PARA TODAS las materias")
    print("  - Relaciones materias_carrera COMPLETAS")
    print("  - Calificaciones CON parciales variables")

if __name__ == "__main__":
    main()
