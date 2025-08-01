# 🤝 Guía de Contribución - Next

¡Gracias por tu interés en contribuir al proyecto **Next**! Este documento te guiará sobre cómo puedes participar de manera efectiva.

## 📋 Tabla de Contenidos

- [🎯 Tipos de Contribución](#-tipos-de-contribución)
- [🚀 Primeros Pasos](#-primeros-pasos)
- [💻 Configuración del Entorno](#-configuración-del-entorno)
- [📝 Estándares de Código](#-estándares-de-código)
- [🐛 Reporte de Bugs](#-reporte-de-bugs)
- [✨ Sugerencias de Características](#-sugerencias-de-características)
- [📄 Documentación](#-documentación)
- [🔄 Proceso de Pull Request](#-proceso-de-pull-request)
- [💬 Comunicación](#-comunicación)

---

## 🎯 Tipos de Contribución

Valoramos diferentes tipos de contribuciones:

### 🐛 Reporte de Bugs
- Identificación de errores o comportamientos inesperados
- Mejoras en la experiencia de usuario
- Problemas de rendimiento

### ✨ Nuevas Características
- Funcionalidades adicionales
- Mejoras en la interfaz de usuario
- Optimizaciones de código

### 📚 Documentación
- Mejoras en el README
- Documentación técnica
- Guías de usuario
- Comentarios en el código

### 🎨 Diseño y UX
- Mejoras visuales
- Optimización de la experiencia de usuario
- Diseño responsive
- Accesibilidad

---

## 🚀 Primeros Pasos

### 📋 Antes de Contribuir

1. **Lee el README completo** para entender el proyecto
2. **Revisa los issues existentes** para evitar duplicados
3. **Familiarízate con el código** explorando la estructura
4. **Únete a la conversación** en discussions o issues

### 🔍 Encuentra algo en lo que trabajar

- 🏷️ Busca issues etiquetados como `good first issue`
- 🆘 Revisa issues marcados como `help wanted`
- 🐛 Reporta bugs que encuentres
- 💡 Propón nuevas ideas en discussions

---

## 💻 Configuración del Entorno

### 📋 Requisitos Previos

```bash
✅ PHP 8.0 o superior
✅ MySQL 8.0 o superior
✅ Servidor web (Apache/Nginx)
✅ Composer (recomendado)
✅ Git
```

### 🔧 Configuración Local

1. **Fork el repositorio**
   ```bash
   # Clona tu fork
   git clone https://github.com/tu-usuario/Next-Tienda.git
   cd Next-Tienda
   ```

2. **Configura la rama upstream**
   ```bash
   git remote add upstream https://github.com/GiuGerlo/Next-Tienda.git
   ```

3. **Configuración de Base de Datos**
   ```bash
   # Copia el archivo de configuración
   cp config/connect.php.example config/connect.php
   # Edita con tus credenciales de BD
   ```

4. **Instala dependencias** (si aplica)
   ```bash
   composer install
   ```

---

## 📝 Estándares de Código

### 🎨 Estilo de Código PHP

```php
<?php
// ✅ Buenas prácticas
class VentaController 
{
    /**
     * Documentación clara de métodos
     */
    public function guardarVenta($datos): array
    {
        // Validación de entrada
        if (empty($datos['cliente'])) {
            throw new InvalidArgumentException('Cliente requerido');
        }
        
        // Lógica clara y comentada
        return $this->procesarVenta($datos);
    }
}
```

### 🎨 Estilo CSS/JavaScript

```css
/* ✅ Nomenclatura clara */
.dashboard-card {
    background: var(--next-bg-primary);
    border-radius: 8px;
    /* Comentarios descriptivos */
}
```

```javascript
// ✅ Funciones bien documentadas
function formatearNumero(numero) {
    // Elimina decimales innecesarios
    return numero % 1 === 0 ? Math.floor(numero) : numero.toFixed(2);
}
```

### 📋 Convenciones de Nombres

- **Archivos PHP**: `snake_case.php`
- **Clases**: `PascalCase`
- **Métodos/Funciones**: `camelCase`
- **Variables**: `snake_case` o `camelCase`
- **CSS Classes**: `kebab-case`
- **IDs HTML**: `camelCase`

---

## 🐛 Reporte de Bugs

### 📝 Template de Bug Report

```markdown
## 🐛 Descripción del Bug
Una descripción clara del problema

## 🔄 Pasos para Reproducir
1. Ve a '...'
2. Haz clic en '...'
3. Scroll hasta '...'
4. Ver error

## ✅ Comportamiento Esperado
Descripción de lo que debería pasar

## ❌ Comportamiento Actual
Descripción de lo que está pasando

## 🖼️ Screenshots
Si es posible, agrega capturas de pantalla

## 🌐 Entorno
- OS: [ej. Windows 10]
- Navegador: [ej. Chrome 91]
- Versión PHP: [ej. 8.1]
- Versión MySQL: [ej. 8.0]

## 📋 Información Adicional
Cualquier contexto adicional sobre el problema
```

---

## ✨ Sugerencias de Características

### 💡 Template de Feature Request

```markdown
## 🎯 Problema/Necesidad
Descripción clara del problema que resuelve

## 💡 Solución Propuesta
Descripción clara de la funcionalidad deseada

## 🔄 Alternativas Consideradas
Otras soluciones que consideraste

## 📋 Contexto Adicional
Screenshots, mockups, referencias
```

---

## 🔄 Proceso de Pull Request

### 📋 Checklist antes del PR

- [ ] 🔍 El código sigue los estándares del proyecto
- [ ] 🧪 Has probado los cambios localmente
- [ ] 📝 Actualizaste la documentación si es necesario
- [ ] 🏷️ El commit tiene un mensaje descriptivo
- [ ] 🔄 Tu rama está actualizada con main

### 📝 Template de Pull Request

```markdown
## 📋 Descripción
Breve descripción de los cambios

## 🎯 Tipo de Cambio
- [ ] 🐛 Bug fix
- [ ] ✨ Nueva característica
- [ ] 💥 Breaking change
- [ ] 📝 Documentación

## 🧪 Testing
Describe las pruebas realizadas

## 📋 Checklist
- [ ] El código sigue el estilo del proyecto
- [ ] He probado los cambios
- [ ] He actualizado la documentación
```

### 🔄 Proceso de Review

1. **Envía el PR** con descripción clara
2. **Espera el review** del equipo
3. **Realiza cambios** si se solicitan
4. **Aprobación** y merge del equipo

---

## 💬 Comunicación

### 📞 Canales de Comunicación

- **📧 Issues**: Para bugs y features
- **💬 Discussions**: Para preguntas generales
- **📧 Email**: contact@artisansthinking.com para temas privados

### 🕒 Tiempos de Respuesta

- **Issues**: 24-48 horas
- **Pull Requests**: 48-72 horas
- **Discussions**: 24-48 horas

---

## 🎉 Reconocimiento

### 🏆 Contribuidores

Todos los contribuidores son reconocidos en:
- Lista de contributors de GitHub
- Sección especial en el README
- Menciones en releases

### 🎁 Beneficios para Contribuidores

- 🌟 Reconocimiento público
- 📈 Experiencia en proyecto real
- 🤝 Networking con desarrolladores
- 📧 Referencias profesionales

---

## 📄 Código de Conducta

### 🤝 Nuestros Valores

- **Respeto**: Tratamos a todos con dignidad
- **Inclusión**: Valoramos la diversidad
- **Colaboración**: Trabajamos juntos hacia objetivos comunes
- **Aprendizaje**: Promovemos el crecimiento mutuo

### ⚖️ Comportamiento Esperado

- ✅ Usa lenguaje inclusivo y respetuoso
- ✅ Acepta críticas constructivas
- ✅ Enfócate en lo mejor para la comunidad
- ✅ Muestra empatía hacia otros miembros

### 🚫 Comportamiento Inaceptable

- ❌ Lenguaje ofensivo o discriminatorio
- ❌ Ataques personales o políticos
- ❌ Acoso público o privado
- ❌ Spam o contenido irrelevante

---

## 📞 Contacto

¿Tienes preguntas sobre cómo contribuir?

- 📧 **Email**: contact@artisansthinking.com
- 🌐 **Website**: [artisansthinking.com](https://artisansthinking.com)
- 💼 **LinkedIn**: [Artisans Thinking](https://linkedin.com/company/artisansthinking)

---

<div align="center">
  <h3>🙏 ¡Gracias por contribuir a Next!</h3>
  <p><strong>Juntos construimos mejores soluciones</strong></p>
  
  <img src="https://img.shields.io/badge/Contributions-Welcome-brightgreen?style=for-the-badge">
  <img src="https://img.shields.io/badge/Made%20with-❤️-red?style=for-the-badge">
</div>
