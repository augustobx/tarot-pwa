# 🔮 INSTRUCCIONES DE PRUEBA - Arquitectura Modular

## ✅ Lo que se ha completado

### 1. Estructura de Carpetas
```
tarot-pwa/
├── index.php                           ✅ Home screen con selector
├── modules/tarot/                      ✅ Módulo Tarot completo
├── modules/astrology/                  ✅ Placeholder
├── modules/numerology/                 ✅ Placeholder
├── shared/api/                         ✅ DB, Auth, Gemini, States
└── shared/css/ & shared/js/            ✅ Recursos compartidos
```

### 2. Base de Datos
- ✅ Columna `module` agregada a tabla `chats`
- ✅ Tabla `user_sessions` creada
- ✅ Migraciones ejecutadas correctamente

### 3. Archivos Migrados
- ✅ `api/auth.php` → `shared/api/auth.php`
- ✅ `api/gemini.php` → `shared/api/gemini.php`
- ✅ `api/db.php` → `shared/api/db.php`
- ✅ `api/conversation_state.php` → `shared/api/conversation_state.php`
- ✅ Todo el módulo tarot movido a `modules/tarot/`

### 4. Tracking de Módulos
- ✅ Todos los INSERT en `tarot_chat.php` incluyen `module='tarot'`
- ✅ Historial de chat ahora identifica qué módulo generó cada mensaje

---

## 🧪 PASOS PARA PROBAR

### TEST 1: Home Screen
1. Abre: **http://localhost/tarot-pwa/index.php**
2. **Esperado:**
   - ✨ Ver carousel con 3 tarjetas (Astrología, Tarot, Numerología)
   - ⬅️➡️ Flechas funcionan
   - 🔘 Indicadores (dots) cambian al navegar
   - 📱 Swipe funciona en móvil

### TEST 2: Acceso a Tarot (Sin Login)
1. En home, clic en **"Consultar Cartas"** (botón de Tarot)
2. **Esperado:**
   - Modal/prompt: "¿Quieres registrarte?"
   - Opciones: Registrarse / Continuar como invitado
3. Clic en **"Continuar sin registro"**
4. **Esperado:**
   - Redirige a `modules/tarot/index.php`
   - Chat carga correctamente
   - Mensaje de bienvenida aparece

### TEST 3: Funcionalidad de Tarot
1. En chat de tarot, escribe: **`quiero 1 carta`**
2. **Esperado:**
   - ✅ Aparece 1 sola carta (no 3)
   - ✅ Carta muestra reverso (luna 🌙) inicialmente
   - ✅ Animación de flip ocurre después de ~200ms
   - ✅ Carta muestra frente (placeholder ⭐ o imagen)
   - ✅ Nombre de carta visible y legible
   - ✅ Etiqueta de posición visible
   - ✅ Interpretación aparece después del flip

3. Verifica en **consola del navegador (F12)**:
   - ⭐ Ver logs de flip animation
   - ❌ No debe haber errores 404 en archivos JS/CSS

### TEST 4: Navegación
1. En módulo Tarot, clic icono **🏠 (top-left)**
2. **Esperado:** Vuelve al home screen
3. Clic en **"Consultar Astros"** (Astrología)
4. **Esperado:**
   - Página "Coming Soon"
   - Links de navegación funcionan
5. Clic **"Volver al Inicio"**
6. **Esperado:** Regresa a home screen

### TEST 5: Login/Registro
1. En módulo Tarot, escribe en chat:
   ```
   entrar tuusuario tucontraseña
   ```
2. **Esperado:**
   - Login exitoso
   - Muestra balance de preguntas
3. Ve al home (http://localhost/tarot-pwa/index.php)
4. **Esperado:**
   - Info de usuario en top-right (nombre + preguntas)
   - Botón "Cerrar Sesión" visible
5. Clic en módulo, **no debe pedir registro**

### TEST 6: Verificación de Base de Datos
1. Abre phpMyAdmin
2. Tabla `chats`:
   - Verifica que columna `module` existe
   - Nuevos mensajes tienen `module = 'tarot'`
3. Tabla `user_sessions`:
   - Debe existir y estar vacía (por ahora)

---

## ⚠️ Si algo no funciona

### Error 404 en archivos
- **Solución:** Verifica rutas en `modules/tarot/index.php`
- Asegúrate que CSS/JS apunten a `../../shared/` o `../../assets/`

### Flip animation no funciona
- Abre **Consola (F12)**
- Busca errores en `tarot.js` o `tarot_cards.css`
- Verifica que `tarot_cards.css` está en `modules/tarot/css/`

### Database error en INSERT
- Ejecuta nuevamente: `php migrate_database.php`
- Verifica que columna `module` existe en tabla `chats`

### Home screen no carga
- Verifica que `shared/api/db.php` existe
- Revisa logs de PHP (`error.log`)

---

## 📊 Próximos Pasos (Futuro)

### Implementar Módulo de Astrología
1. Crear `modules/astrology/api/astrology_chat.php`
2. Copiar estructura de `tarot_chat.php`
3. Adaptar prompts AI para astrología
4. Agregar cálculos de carta natal

### Implementar Módulo de Numerología
1. Crear `modules/numerology/api/numerology_chat.php`
2. Implementar cálculos numerológicos
3. Adaptar prompts AI para numerología

### Mejoras Visuales
- Agregar imágenes reales para tarjetas de módulos
- Personalizar colores por módulo (Astrología = azul, etc.)
- Mejorar animaciones del carousel

---

## 🎯 Archivos Clave para Revisar

Si necesitas modificar algo:

**Home Screen:**
- [index.php](file:///c:/xampp/htdocs/tarot-pwa/index.php)

**Módulo Tarot:**
- [modules/tarot/index.php](file:///c:/xampp/htdocs/tarot-pwa/modules/tarot/index.php)
- [modules/tarot/api/tarot_chat.php](file:///c:/xampp/htdocs/tarot-pwa/modules/tarot/api/tarot_chat.php)
- [modules/tarot/js/tarot.js](file:///c:/xampp/htdocs/tarot-pwa/modules/tarot/js/tarot.js)

**Componentes Compartidos:**
- [shared/api/db.php](file:///c:/xampp/htdocs/tarot-pwa/shared/api/db.php)
- [shared/api/auth.php](file:///c:/xampp/htdocs/tarot-pwa/shared/api/auth.php)
- [shared/api/gemini.php](file:///c:/xampp/htdocs/tarot-pwa/shared/api/gemini.php)
- [shared/css/global.css](file:///c:/xampp/htdocs/tarot-pwa/shared/css/global.css)
- [shared/js/chat_base.js](file:///c:/xampp/htdocs/tarot-pwa/shared/js/chat_base.js)

**Migraciones:**
- [migrate_database.php](file:///c:/xampp/htdocs/tarot-pwa/migrate_database.php)

---

## ✅ Checklist de Verificación

- [ ] Home screen carga sin errores
- [ ] Carousel funciona (flechas + dots + swipe)
- [ ] Clic en Tarot → muestra prompt de registro (si no logged in)
- [ ] Chat de Tarot funciona
- [ ] `quiero 1 carta` muestra exactamente 1 carta
- [ ] Flip animation funciona suavemente
- [ ] Botón Home regresa a selector
- [ ] Login funciona
- [ ] User info se muestra en home cuando logged in
- [ ] Módulos de Astrología/Numerología muestran placeholders
- [ ] No hay errores 404 en consola
- [ ] Base de datos tiene columna `module` en `chats`

---

**¡Todo listo para probar! 🚀**
