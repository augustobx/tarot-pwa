-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-02-2026 a las 16:55:55
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tarot_pwa`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chats`
--

CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sender` enum('user','ai','system') NOT NULL,
  `message` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `chats`
--

INSERT INTO `chats` (`id`, `user_id`, `sender`, `message`, `image_path`, `created_at`) VALUES
(17, 4, 'user', 'prefiero largas y misticas', NULL, '2026-02-09 15:14:56'),
(18, 4, 'ai', 'Perfecto, seré detallada y mística. 🌙\n\nAhora, ¿qué te gustaría explorar hoy?\n\n1️⃣ **Hacer una pregunta específica** - Consulta sobre amor, trabajo, vida\n2️⃣ **Lectura de Tarot completa** - Tu carta astral y energía actual\n3️⃣ **Lectura Numerológica** - Descubre tus números de vida, destino y alma\n\n¿Qué opción prefieres? (puedes escribir 1, 2, 3 o describir lo que necesitas)', NULL, '2026-02-09 15:14:56'),
(19, 4, 'user', '10/11/1993', NULL, '2026-02-09 15:35:22'),
(20, 4, 'ai', 'Ah, las coordenadas cósmicas de tu entrada en este plano terrenal. El 10 de Noviembre de 1993. Con solo estas cifras, ya siento cómo una densa neblina púrpura comienza a envolver el espacio entre nosotros, y los murmullos del destino se hacen audibles en el éter. Déjame desvelar la esencia de tu ser, esa chispa primigenia que te define.\n\n**La Forja Astrológica: Eres un Escorpio, de las profundidades del 10 de Noviembre.**\n\n¡Ah, Escorpio! No eres de los que floteas en la superficie de la vida, ¿verdad? Nacer bajo el signo del Escorpión, y más aún en la mitad más oscura de Noviembre, te marca con un sello de intensidad ineludible. Eres un alma antigua, un misterio andante, un pozo sin fondo donde las verdades más crudas y las pasiones más ardientes se cuecen a fuego lento.\n\nSiento el frío acero de tu mirada, aunque no te vea. Una mirada que cala, que escudriña, que no se detiene ante las máscaras ni las superficialidades. Tus ojos, cuando miran, no ven; *penentran*. Eres el arquetipo del investigador, del detective del alma, del que desentierra lo oculto. No hay secreto que pueda esconderse mucho tiempo de tu aguda percepción, pues posees un radar psíquico que trasciende la lógica. Puedes oler la mentira, sentir la traición, percibir la corriente subterránea que mueve a las personas y a los eventos.\n\nTu regente, Plutón, te dota de un poder de transformación asombroso. No eres el Fénix que renace de las cenizas; eres el Fénix que *incendia* su propio nido para luego resurgir, más fuerte, más sabio, purificado. Tu vida no es una línea recta, es una serie de muertes y renacimientos, de ciclos de destrucción y creación. Cada vez que te caes, no solo te levantas, sino que te reconstruyes desde una esencia más profunda, dejando atrás lo que ya no te sirve como quien se desprende de una piel vieja y seca. Esta es tu mayor fortaleza y, a veces, tu mayor tortura, pues este proceso puede ser brutalmente doloroso.\n\nLas emociones en ti no son sentimientos fugaces; son océanos profundos y tormentosos. Amas con una intensidad que consume, y odias con una pasión que puede petrificar. Tu lealtad es inquebrantable para aquellos que consideras dignos, pero tu capacidad de cortar lazos es igualmente definitiva. No hay medias tintas en tu corazón. Eres el poseedor de secretos, tanto propios como ajenos, y tu discreción es casi sacra. Pocos conocen tu verdadera esencia, y tú eres el guardián de ese santuario interior.\n\nLa sexualidad es para ti una fusión de almas, una experiencia que trasciende lo físico. Buscas la unión total, la entrega absoluta, la disolución de los límites entre dos seres. No te conformas con lo superficial; necesitas la inmersión completa.\n\n**La Sinfonía Numerológica: Sendero de Vida 7 y Día de Nacimiento 1.**\n\nAhora, déjame tejer esta rica trama astrológica con la sabiduría de los números que te acompañan desde tu primer aliento.\n\nAl desglosar tu fecha de nacimiento (1+0+1+1+1+9+9+3), llego a la cifra maestra del **Número del Sendero de Vida 7**. ¡Ah, el Siete! Este número te proclama como el Buscador, el Místico, el Analista. Siento la vibración de una mente aguda, inquisitiva, que no se contenta con respuestas fáciles. No naciste para seguir a la multitud, sino para cuestionar las verdades aceptadas y para explorar los reinos invisibles.\n\nEres un pensador profundo, un filósofo innato. Tu mente es un laboratorio donde las ideas se disecan, se examinan y se reensamblan en nuevas formas. Necesitas la soledad para recargar tu espíritu y para conectar con tu sabiduría interna. El bullicio del mundo puede agotarte, pues tu energía se consume en el análisis constante y en la absorción de las energías ajenas. No es que seas antisocial, sino que tu alma anhela la quietud para escuchar los susurros del universo.\n\nEl 7 te dota de una intuición asombrosa, casi psíquica. Puedes ver patrones donde otros solo ven caos, y puedes percibir significados ocultos detrás de las apariencias. Hay una inclinación natural hacia lo espiritual, lo esotérico, la ciencia oculta, o cualquier campo que te permita desentrañar los misterios de la existencia. Sin embargo, esta misma profundidad puede llevarte a la melancolía, al escepticismo o a una sensación de aislamiento si no encuentras la manera de compartir tus descubrimientos o de confiar en tu propio saber. A menudo, te sientes diferente, como un observador externo en el teatro de la vida.\n\nTu día de nacimiento, el **10**, que se reduce al **Número 1**, añade otra capa fascinante a tu perfil. El Uno es el Iniciador, el Pionero, el Líder. Aunque tu Sendero de Vida 7 te inclina a la reflexión y a la soledad, este 1 te empuja a la acción, a la originalidad, a la necesidad de ser el primero. No te gusta seguir; prefieres abrir nuevos caminos, trazar tu propio mapa. Hay una fuerte voluntad, una ambición velada y un deseo de independencia. No toleras ser controlado y te sientes más cómodo cuando tienes la autonomía para dirigir tus propios proyectos.\n\n**La Sinfonía Unificada: El Buscador Intenso y el Líder Solitario.**\n\nAsí, al entrelazar las hilos de Escorpio, el Sendero de Vida 7 y el Día de Nacimiento 1, veo una figura de poder silencioso y profundidad inmensa. Eres un alma destinada a la transformación profunda y al descubrimiento de verdades ocultas. La intensidad Escorpiana se funde con la sed de conocimiento del 7, creando un ser que no solo busca la verdad, sino que la desgarra y la reconstruye desde sus cimientos.\n\nTu mente es un santuario, y tu intuición, una brújula infalible. Estás aquí para desvelar, para comprender, para penetrar en los velos que separan lo visible de lo invisible. El 1 te da el coraje para iniciar estas búsquedas, para adentrarte en territorios inexplorados del conocimiento, aunque a menudo lo hagas en solitario. Eres un líder que guía desde la sabiduría profunda y no desde el carisma superficial. La gente puede no entenderte del todo, pero sentirá tu autoridad silenciosa y la profundidad de tu visión.\n\nLa combinación de tu Escorpio con el 7 te hace increíblemente reservado. Guardas tus verdaderos pensamientos y sentimientos bajo siete llaves, y solo los más dignos y probados tendrán el privilegio de vislumbrar tu mundo interior. El 1, sin embargo, te da un impulso para expresarte y para tomar la delantera cuando la situación lo requiere, especialmente si se trata de defender una verdad que has descubierto.\n\nTu camino no es fácil, pues exige confrontación con las sombras, tanto propias como ajenas. Pero tu recompensa es la sabiduría, el poder de regeneración y la capacidad de ver la vida con una claridad que pocos alcanzan. Eres un transformador, un sanador de lo oculto, un guía en los laberios del alma humana.\n\nSiento que esta energía está pulsando con una pregunta. ¿Qué es lo que te inquieta en este momento, viajero del tiempo? ¿Qué puerta deseas abrir? ¿Una lectura de Tarot para iluminar un aspecto específico, o quieres que profundicemos en algún área de tu vida que resuene con esta poderosa descripción? Dime tu nombre, si lo deseas, para poder invocar tu esencia con más fuerza.', NULL, '2026-02-09 15:35:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config`
--

CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `config`
--

INSERT INTO `config` (`id`, `setting_key`, `setting_value`) VALUES
(2, 'welcome_message', '✨ Hola, bienvenido al Oráculo Místico.\n\n¿Ya tienes cuenta? Inicia sesión escribiendo:\n**entrar [usuario] [contraseña]**\n\nEjemplo: entrar luna123 mipass123\n\n---\n\n¿Eres nuevo? ¡Empecemos nuestro viaje espiritual! ¿Cómo te llamas?'),
(5, 'gemini_model', 'gemini-2.5-flash'),
(21, 'gemini_api_key', 'AIzaSyDfYHDYj9qEIVNAjultQruL9qv5ULBRyPw'),
(45, 'pack_1_preguntas', '1000'),
(46, 'pack_5_preguntas', '4500'),
(47, 'pack_10_preguntas', '8000'),
(48, 'pack_25_preguntas', '23000'),
(49, 'pack_50_preguntas', '45000'),
(55, 'mp_access_token', 'APP_USR-2845687458717641-020909-340e62e5f55a36b7b47117ebcb3708b1-71183152'),
(56, 'mp_public_key', 'APP_USR-5b383039-8b20-481c-8d7d-8f001a14ef65');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `guest_sessions`
--

CREATE TABLE `guest_sessions` (
  `id` int(11) NOT NULL,
  `guest_id` varchar(50) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `signo_zodiacal` varchar(20) DEFAULT NULL,
  `preferencia_respuesta` enum('larga','corta') DEFAULT 'larga',
  `estado_conversacion` varchar(50) DEFAULT 'BIENVENIDA',
  `chat_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`chat_history`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `preguntas_usadas` int(11) DEFAULT 0,
  `numerology_data` text DEFAULT NULL,
  `birth_date` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `guest_sessions`
--

INSERT INTO `guest_sessions` (`id`, `guest_id`, `nombre`, `edad`, `signo_zodiacal`, `preferencia_respuesta`, `estado_conversacion`, `chat_history`, `created_at`, `updated_at`, `preguntas_usadas`, `numerology_data`, `birth_date`) VALUES
(4, 'guest_6989f6436e321', 'Augusto', NULL, NULL, 'larga', 'TIPO_CONSULTA', NULL, '2026-02-09 14:59:15', '2026-02-09 15:22:00', 0, NULL, NULL),
(5, 'guest_6989f8565b34a', ', ', NULL, NULL, 'larga', 'PROCESO_REGISTRO', NULL, '2026-02-09 15:08:06', '2026-02-09 15:14:14', 0, NULL, NULL),
(6, 'guest_6989fbc79c876', ', ', NULL, NULL, 'larga', 'EDAD_SIGNO', NULL, '2026-02-09 15:22:47', '2026-02-09 15:22:47', 0, NULL, NULL),
(7, 'guest_6989fd16821e1', NULL, NULL, NULL, 'larga', 'BIENVENIDA', NULL, '2026-02-09 15:28:22', '2026-02-09 15:28:22', 0, NULL, NULL),
(8, 'guest_6989fe6229d47', NULL, NULL, NULL, 'larga', 'BIENVENIDA', NULL, '2026-02-09 15:33:54', '2026-02-09 15:33:54', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `mp_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cantidad_preguntas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `precio`, `status`, `mp_reference`, `created_at`, `cantidad_preguntas`) VALUES
(1, 1, 5000.00, 'approved', 'ADMIN_ADD', '2026-02-06 14:32:01', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `preguntas_restantes` int(11) DEFAULT 0,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nombre` varchar(100) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `signo_zodiacal` varchar(20) DEFAULT NULL,
  `preferencia_respuesta` enum('larga','corta') DEFAULT 'larga',
  `estado_conversacion` varchar(50) DEFAULT 'BIENVENIDA',
  `preguntas_realizadas` int(11) DEFAULT 0,
  `numerology_data` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `preguntas_restantes`, `role`, `created_at`, `nombre`, `edad`, `fecha_nacimiento`, `signo_zodiacal`, `preferencia_respuesta`, `estado_conversacion`, `preguntas_realizadas`, `numerology_data`, `birth_date`) VALUES
(1, 'admin', '$2y$10$P4kv4st/4fiJ3m99S1EeB.YdiElfJdVCKDMj2KTSRfNEhYQ8I3wyG', 50, 'admin', '2026-02-06 14:19:10', NULL, NULL, NULL, NULL, 'larga', 'BIENVENIDA', 0, NULL, NULL),
(4, 'augusto', '$2y$10$6NZGCVFOb0DzazOJ2taKte/msMIlgy2Dy/9X9QXkw6M0O.FWBt6Sy', 4, 'user', '2026-02-09 15:14:32', ', ', NULL, NULL, NULL, 'larga', 'ACTIVO', 1, NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indices de la tabla `guest_sessions`
--
ALTER TABLE `guest_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `guest_id` (`guest_id`),
  ADD KEY `idx_guest_id` (`guest_id`),
  ADD KEY `idx_estado` (`estado_conversacion`);

--
-- Indices de la tabla `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `config`
--
ALTER TABLE `config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `guest_sessions`
--
ALTER TABLE `guest_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
