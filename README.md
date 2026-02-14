# API de Gestión de Tareas y Proyectos (mi-symfony-api)

![Symfony Version](https://img.shields.io/badge/Symfony-6.4-green.svg)
![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)
![License](https://img.shields.io/badge/License-Proprietary-orange.svg)

Una API REST completa desarrollada con Symfony 6.4 para la gestión de proyectos y tareas con control de tiempo, tarifas personalizadas por usuario/proyecto y cálculo automático de valores.

---

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Arquitectura del Proyecto](#-arquitectura-del-proyecto)
- [Entidades del Dominio](#-entidades-del-dominio)
- [API Endpoints](#-api-endpoints)
- [Autenticación](#-autenticación)
- [Documentación de la API](#-documentación-de-la-api)
- [Comandos Útiles](#-comandos-útiles)
- [Tecnologías Utilizadas](#-tecnologías-utilizadas)
- [Capturas de Pantalla](#-capturas-de-pantalla)

---

## ✨ Características

- **Gestión de Usuarios**: Sistema de registro, login y recuperación de contraseña
- **Proyectos Múltiples**: Los usuarios pueden pertenecer a múltiples proyectos
- **Tarifas Personalizadas**: Tarifa por hora configurable por usuario/proyecto
- **Control de Tiempo**: Inicio, pausa y finalización de tareas con temporizador
- **Cálculo Automático**: Valor total = horas trabajadas × tarifa por hora
- **API REST**: Endpoints completos para integración con otras aplicaciones
- **Interfaz Web**: Vistas responsive con Twig para gestión visual
- **Seguridad**: Autenticación JWT y protección de rutas

---

## 📌 Requisitos

- PHP 8.1 o superior
- Composer
- PostgreSQL 12+ (o MySQL/MariaDB)
- Symfony CLI (opcional pero recomendado)

---

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone <repository-url> mi-symfony-api
cd mi-symfony-api
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar variables de entorno

Copia el archivo `.env` y configúralo según tu entorno:

```bash
# Base de datos (PostgreSQL recomendado)
DATABASE_URL="postgresql://app:app@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

# Clave secreta para JWT (genera una clave aleatoria)
JWT_SECRET=tu_clave_secreta_aqui

# Configuración de mailer (para recuperación de contraseña)
MAILER_DSN=nul://null
```

### 4. Crear la base de datos y ejecutar migraciones

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. (Opcional) Cargar datos de prueba

```bash
php bin/console doctrine:fixtures:load
```

### 6. Iniciar el servidor

```bash
# Servidor de desarrollo Symfony
php -S localhost:8000 -t public

# O con Symfony CLI
symfony server:start
```

---

## ⚙️ Configuración

### Variables de Entorno (.env)

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `DATABASE_URL` | Conexión a la base de datos | `postgresql://user:pass@localhost:5432/dbname` |
| `JWT_SECRET` | Clave secreta para tokens JWT | `your-secret-key-min-32-chars` |
| `MAILER_DSN` | Configuración del servidor de correo | `smtp://user:pass@smtp.example.com` |

### Generar claves JWT

```bash
# Generar clave privada JWT
openssl genrsa -out config/jwt/private.pem 4096

# Generar clave pública JWT
openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem
```

---

## 🏗️ Arquitectura del Proyecto

```
mi-symfony-api/
├── config/                  # Configuración de Symfony
│   ├── packages/           # Paquetes de configuración
│   ├── routes/             # Definición de rutas
│   └── jwt/               # Claves JWT
├── src/
│   ├── Controller/         # Controladores (API + Web)
│   │   ├── AuthApiController.php    # Endpoints de autenticación API
│   │   ├── TaskApiController.php     # CRUD de tareas API
│   │   ├── TaskWebController.php     # Vistas web de tareas
│   │   ├── ProjectCrudController.php  # CRUD de proyectos
│   │   └── ...
│   ├── Entity/             # Entidades Doctrine (Dominio)
│   │   ├── User.php
│   │   ├── Project.php
│   │   ├── Task.php
│   │   └── UserProject.php
│   ├── Repository/         # Repositorios Doctrine
│   ├── Form/               # Tipos de formulario Symfony
│   ├── EventSubscriber/    # Suscriptores de eventos
│   └── DataFixtures/       # Datos de prueba
├── templates/              # Plantillas Twig
├── migrations/             # Migraciones Doctrine
├── public/                 # Archivo público (index.php)
└── bin/                    # Scripts de consola
```

### Patrones de Diseño Utilizados

- **Repository Pattern**: Abstracción del acceso a datos
- **Doctrine ORM**: Mapeo objeto-relacional
- **Symfony Forms**: Gestión de formularios
- **JWT Authentication**: Token-based auth

---

## 📦 Entidades del Dominio

### User (`src/Entity/User.php`)

Representa un usuario del sistema con autenticación y propiedades:

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private ?int $id;
    private ?string $email;
    private array $roles;        // Roles: ROLE_USER, ROLE_ADMIN
    private ?string $password;   // Contraseña hasheada
    private ?string $resetToken; // Token para recuperación
    private ?\DateTime $resetTokenExpiresAt;
}
```

**Relaciones:**
- OneToMany con `Task` (tareas propias)
- OneToMany con `UserProject` (proyectos asociados)

---

### Project (`src/Entity/Project.php`)

Representa un proyecto que puede tener múltiples tareas:

```php
class Project
{
    private ?int $id;
    private ?string $name;
    private ?string $descripcion;
    private Collection $userProjects; // Usuarios asociados
    private Collection $tasks;         // Tareas del proyecto
    
    // Método para obtener tarifa por usuario
    public function getHourlyRateForUser(User $user): float;
}
```

---

### Task (`src/Entity/Task.php`)

Tarea con control de tiempo integrado:

```php
class Task
{
    private ?int $id;
    private ?string $description;
    private ?string $hoursSpent;      // Horas registradas
    private ?\DateTime $createdAt;
    private ?User $owner;
    private ?Project $project;
    
    // Control de tiempo
    private ?\DateTime $startedAt;
    private ?\DateTime $lastResumeAt;
    private ?\DateTime $finishedAt;
    private bool $isRunning;
    private ?string $accumulatedTime;
    
    // Métodos de control
    public function start(): void;
    public function pause(): void;
    public function stop(): void;
    public function getCurrentHours(): string;
}
```

---

### UserProject (`src/Entity/UserProject.php`)

Relación muchos a muchos entre usuarios y proyectos con tarifa personalizada:

```php
class UserProject
{
    private ?int $id;
    private ?User $owner;
    private ?Project $project;
    private ?float $hourlyRate;  // Tarifa por hora específica
}
```

---

## 🔌 API Endpoints

### Autenticación

| Método | Endpoint | Descripción | Público |
|--------|----------|--------------|---------|
| POST | `/api/login` | Iniciar sesión (JWT) | ✅ |
| POST | `/api/register` | Registrar nuevo usuario | ✅ |
| POST | `/api/forgot-password` | Solicitar recuperación | ✅ |
| POST | `/api/reset-password` | Restablecer contraseña | ✅ |

### Tareas (API)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/tasks` | Listar todas las tareas |
| POST | `/api/tasks` | Crear nueva tarea |
| GET | `/api/tasks/{id}` | Ver tarea específica |
| PUT | `/api/tasks/{id}` | Actualizar tarea |
| DELETE | `/api/tasks/{id}` | Eliminar tarea |
| POST | `/api/tasks/{id}/start` | Iniciar temporizador |
| POST | `/api/tasks/{id}/pause` | Pausar temporizador |
| POST | `/api/tasks/{id}/stop` | Detener temporizador |

### Proyectos (API)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/projects` | Listar proyectos |
| POST | `/api/projects` | Crear proyecto |
| GET | `/api/projects/{id}` | Ver proyecto |

### Consultas Especiales

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/users/{id}/tasks` | Tareas con valor calculado |
| GET | `/api/users/{id}/total-value` | Valor total del usuario |

---

## 🔐 Autenticación

### Login (JWT)

```bash
# Request
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "your-password"
}

# Response
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
        "id": 1,
        "email": "user@example.com"
    }
}
```

### Uso del Token

```bash
# Incluir token en headers
Authorization: Bearer <your-token>
```

### Endpoints Protegidos

Todos los endpoints bajo `/api` (excepto login y register) requieren autenticación JWT.

---

## 📚 Documentación de la API

La documentación interactiva está disponible en:

```
/api/doc
```

Esta documentación es generada automáticamente por **NelmioApiDocBundle** y permite probar los endpoints directamente desde el navegador.

---

## 💻 Comandos Útiles

### Doctrine

```bash
# Crear base de datos
php bin/console doctrine:database:create

# Ejecutar migraciones
php bin/console doctrine:migrations:migrate

# Crear entidad
php bin/console make:entity

# Crear controlador
php bin/console make:controller

# Ver SQL de migraciones
php bin/console doctrine:schema:update --dump-sql
```

### Symfony

```bash
# Limpiar caché
php bin/console cache:clear

# Ver rutas
php bin/console debug:router

# Ver servicios
php bin/console debug:container

# Generar claves JWT
php bin/console lexik:jwt:generate-keypair
```

### Desarrollo

```bash
# Iniciar servidor
php -S localhost:8000 -t public

# Con Symfony CLI
symfony serve

# Ver profiler
symfony server:dev
```

---

## 🛠️ Tecnologías Utilizadas

### Core
- **Symfony 6.4** - Framework PHP
- **PHP 8.1+** - Lenguaje de programación
- **Doctrine ORM 3.6** - Mapeo objeto-relacional

### Base de Datos
- **PostgreSQL 16** - Base de datos principal
- **Doctrine Migrations** - Gestión de esquemas

### Seguridad
- **LexikJWTAuthenticationBundle** - Autenticación JWT
- **Symfony Security** - Autorización y roles

### API
- **NelmioApiDocBundle** - Documentación interactiva
- **FOSRESTBundle** - RESTful APIs (configurado)

### Frontend
- **Twig** - Motor de plantillas
- **Symfony UX Turbo** - SPA-like experience
- **Stimulus** - JavaScript framework

### Herramientas
- **Composer** - Gestión de dependencias
- **PHPUnit** - Testing
- **DoctrineFixturesBundle** - Datos de prueba
- **Monolog** - Logging

---

## 📸 Capturas de Pantalla

### Interfaz de Tareas

![Tasks Interface](https://github.com/user-attachments/assets/11798f0a-c37b-4248-89e1-8d25ae8735e2)

### Dashboard de Proyectos

![Projects Dashboard](https://github.com/user-attachments/assets/c38a5699-71f1-46ba-8361-a00c83d55442)

---

## 🤝 Contribuir

1. Fork el repositorio
2. Crea una rama (`git checkout -b feature/nueva-caracteristica`)
3. Commit tus cambios (`git commit -am 'Agregar nueva característica'`)
4. Push a la rama (`git push origin feature/nueva-caracteristica`)
5. Crea un Pull Request

---

## 📄 Licencia

Este proyecto es propietario. Todos los derechos reservados.

---

## 📞 Soporte

Para dudas o problemas, por favor abrir un issue en el repositorio.

---

*Documentación actualizada para mi-symfony-api v1.0.0*
