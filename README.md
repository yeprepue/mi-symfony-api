# API de Gestión de Tareas y Proyectos

## Características
- ✅ Usuarios con múltiples proyectos
- ✅ Tarifa personalizada por usuario/proyecto
- ✅ Registro de tareas con horas trabajadas
- ✅ API REST para consultas
- ✅ Vista web responsive
- ✅ Cálculo automático de valores

## Instalación
1. `composer install`
2. Configurar `.env` con PostgreSQL
3. `php bin/console doctrine:migrations:migrate`
4. `php -S localhost:8000 -t public`

## Endpoints
- `GET /api/users/{id}/tasks` - Tareas con valor calculado
- `GET /users/{id}/tasks` - Vista web

## Tecnologías
- Symfony 6.4, PostgreSQL, Doctrine, Twig