-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 22-04-2026 a las 20:48:06
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `robotschool_academy`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades`
--

CREATE TABLE `actividades` (
  `id` int(10) UNSIGNED NOT NULL,
  `tema_id` int(10) UNSIGNED NOT NULL,
  `rubrica_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Rubrica para evaluar esta actividad (opcional)',
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('armado','programacion','investigacion','reto','proyecto','exposicion','taller','otro') NOT NULL DEFAULT 'taller',
  `duracion_min` int(10) UNSIGNED DEFAULT NULL COMMENT 'Duracion estimada en minutos',
  `materiales` text DEFAULT NULL COMMENT 'Materiales o kit requerido',
  `orden` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id` int(10) UNSIGNED NOT NULL,
  `sesion_id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `estado` enum('presente','tarde','ausente','excusa') NOT NULL DEFAULT 'ausente',
  `observacion` varchar(255) DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED NOT NULL COMMENT 'usuario_id quien registra',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asistencia`
--

INSERT INTO `asistencia` (`id`, `sesion_id`, `matricula_id`, `estado`, `observacion`, `registrado_por`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'presente', NULL, 11, '2026-04-20 19:11:37', '2026-04-20 19:11:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL COMMENT 'Ruta relativa en /uploads/cursos/',
  `introduccion` text DEFAULT NULL,
  `objetivos` text DEFAULT NULL,
  `edad_min` tinyint(3) UNSIGNED DEFAULT NULL,
  `edad_max` tinyint(3) UNSIGNED DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tipo_valor` enum('mensual','semestral') NOT NULL DEFAULT 'mensual',
  `cupo_maximo` int(10) UNSIGNED NOT NULL DEFAULT 20,
  `publicado` tinyint(1) NOT NULL DEFAULT 0,
  `orden` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `nombre`, `imagen`, `introduccion`, `objetivos`, `edad_min`, `edad_max`, `valor`, `tipo_valor`, `cupo_maximo`, `publicado`, `orden`, `created_at`, `updated_at`) VALUES
(1, 'LEGO BOOST Robótica', 'curso_1774993557_791.jpg', 'De Construir Castillos a Programar Sueños 🚀\r\n¿Recuerdas la primera vez que tu hijo unió dos ladrillos de LEGO y te mostró con orgullo su creación? Esa emoción es solo el principio. Hoy, te invitamos a dar el siguiente paso: el momento en que sus creaciones cobran vida.\r\nImagina a tu pequeño no solo siguiendo instrucciones, sino dándole \"voz\" a un robot, enseñándole a bailar a un gato mecánico o componiendo música con una guitarra que él mismo inventó. No se trata solo de piezas de plástico; se trata de darle las herramientas para que entienda el lenguaje del futuro. Al programar su primer movimiento, tu hijo no solo está jugando; está descubriendo que es capaz de resolver problemas, de pensar con lógica y, sobre todo, de que su imaginación no tiene límites.\r\nRegálales la confianza de saber que ellos pueden construir el mundo que imaginan.', 'Objetivos del Viaje Robótico 🎯\r\nPara que esta aventura sea tan educativa como divertida, nos enfocaremos en cuatro pilares:\r\nAlfabetización Digital Temprana: Aprender los fundamentos de la programación (lógica de bloques) de forma natural, antes incluso de escribir código complejo.\r\nResolución Creativa de Problemas: Fomentar el pensamiento crítico para superar retos técnicos: \"¿Por qué mi robot no gira a la derecha?\".\r\nDesarrollo de la Motricidad Fina y Espacial: Perfeccionar la precisión manual y la comprensión de estructuras físicas complejas mediante el montaje de engranajes y sensores.\r\nAutoconfianza y Resiliencia: Transformar el \"error\" en un paso necesario para el éxito, celebrando cada pequeño logro cuando el motor finalmente se activa.', 6, 8, 200000.00, 'mensual', 10, 1, 0, '2026-03-29 21:57:27', '2026-03-31 16:45:57'),
(2, 'LEGO Spyke Prime', 'curso_1774993477_968.jpeg', '¿Quieres que tu hijo deje de ser solo un espectador de la tecnología y empiece a crearla?\r\nEn un mundo que cambia a pasos agigantados, saber programar y entender la robótica se ha convertido en el \"nuevo superpoder\". Con nuestro curso de LEGO SPIKE, transformamos la curiosidad natural de los niños en habilidades reales de ingeniería y computación.\r\nNo es solo armar robots; es aprender a pensar. Es ver cómo un montón de ladrillos cobra vida gracias al código que ellos mismos escribieron. Aquí, el error es parte del juego y cada reto superado construye una confianza inquebrantable.\r\n¡Dale a tu hijo las herramientas para diseñar el futuro, un ladrillo a la vez!', '🎯 Objetivos del Curso\r\nDominio de la Lógica de Programación: Aprender los fundamentos del código (bucles, sensores, condicionales) de forma visual y divertida.\r\nDesarrollo del Pensamiento Computacional: Fomentar la capacidad de descomponer problemas complejos en pasos pequeños y solucionables.\r\nHabilidades de Ingeniería Práctica: Entender el funcionamiento de engranajes, palancas y motores aplicados a robots reales.\r\nFomento de las Soft Skills: Potenciar el trabajo en equipo, la comunicación asertiva y la resiliencia ante la frustración.\r\nCreatividad Aplicada: Incentivar la creación de soluciones originales a problemas del mundo real.', 8, NULL, 210000.00, 'mensual', 20, 1, 0, '2026-03-31 12:07:35', '2026-03-31 16:44:37'),
(3, 'LEGO WeDO', 'curso_1774993421_234.jpg', '🚀 Título del Curso: \"Pequeños Ingenieros: Crea, Programa y Despierta tu Genio con LEGO WeDo\"\r\nDescripción para Padres:\r\n¿Sabías que el juego favorito de tu hijo puede ser la puerta de entrada a las carreras del futuro? En este curso, los niños no solo arman figuras; ¡les dan vida! Usando la tecnología de LEGO WeDo, transformamos el tiempo de pantalla en un laboratorio de invención.\r\nImagina a tu hijo construyendo un satélite espacial o un robot de rescate y luego programándolo para que se mueva y reaccione al entorno. Aquí, el error es parte de la aventura y cada desafío resuelto construye confianza, lógica y pensamiento crítico. No solo estamos enseñando robótica; estamos entrenando a los próximos innovadores que diseñarán el mundo. ¡Dale a tu hijo el superpoder de crear tecnología, no solo de consumirla!', 'Para que los papás vean el valor académico, planteamos estos objetivos:\r\nFundamentos de Ingeniería: Comprender el uso de engranajes, palancas y poleas para crear movimiento físico.\r\nPensamiento Computacional: Aprender a descomponer problemas complejos en pasos lógicos mediante la programación por bloques.\r\nInvestigación Científica: Observar, predecir y probar hipótesis a través de modelos robóticos inspirados en la vida real.\r\nHabilidades Blandas (Soft Skills): Fomentar el trabajo en equipo, la comunicación de ideas y la resiliencia ante retos técnicos.', 5, 7, 200000.00, 'mensual', 10, 1, 0, '2026-03-31 14:13:09', '2026-03-31 16:43:41'),
(4, 'LEGO Ev3 Mindstorms', 'curso_1775194696_757.jpg', '🚀 ¡Prepara a tu hijo para inventar el futuro con LEGO EV3!\r\nQueridos papás:\r\n¿Se han fijado cómo brilla la mirada de un niño cuando logra que algo se mueva por sí solo? En nuestro curso de Robótica con LEGO EV3, transformamos ese asombro en habilidad real.\r\nNo se trata solo de \"jugar con piezas\"; se trata de que sus hijos dejen de ser solo consumidores de tecnología para convertirse en creadores. Aquí, el error es parte del juego y cada desafío superado construye una confianza que les servirá para toda la vida. Mientras ellos creen que están jugando, están aprendiendo lógica de programación, ingeniería y resolución de problemas.\r\n¡Dales la oportunidad de construir sus propios robots y ver cómo sus ideas cobran vida! El futuro no se espera, ¡se construye pieza a pieza! 🧱🤖', '🎯 Objetivos del Curso\r\nDominar la Lógica de Programación: Aprender a pensar de forma secuencial y lógica utilizando bloques de código.\r\nEntender la Mecánica: Comprender cómo funcionan los engranajes, palancas y motores para generar movimiento.\r\nInteracción con el Entorno: Aprender a usar sensores (luz, ultrasonido, tacto) para que el robot \"sienta\" y tome decisiones.\r\nFomentar el Pensamiento Crítico: Resolver retos complejos mediante el ensayo, error y la optimización de diseños.\r\nTrabajo en Equipo: Colaborar en proyectos conjuntos, comunicando ideas y compartiendo roles de liderazgo.', 9, NULL, 200000.00, 'mensual', 6, 1, 0, '2026-04-02 23:39:58', '2026-04-22 20:35:14'),
(5, 'LEGO Inventor Mindstorm', 'curso_1775233521_237.jpg', '🧱 ¡Construye el Futuro, un Ladrillo a la Vez!\r\n¿Alguna vez has visto a tu hijo perderse en su imaginación mientras construye algo nuevo? Esa curiosidad es el motor de los grandes innovadores. En nuestro curso LEGO® Inventor, no solo jugamos; transformamos esa chispa creativa en habilidades reales de ingeniería, programación y resolución de problemas.\r\nHoy, el mundo no solo necesita personas que sigan instrucciones, sino mentes capaces de inventar las soluciones del mañana. Al matricular a tu hijo, le estás dando las herramientas para que deje de ser un consumidor de tecnología y se convierta en su creador. ¡Ven y mira cómo sus ideas cobran vida y movimiento!', '🎯 Objetivos del Programa\r\nNuestro objetivo es empoderar a los estudiantes a través del aprendizaje práctico (Learning by Doing):\r\nDominio de la Robótica: Comprender la relación entre hardware (sensores/motores) y software (programación).\r\nPensamiento Lógico: Desarrollar algoritmos para resolver retos complejos de forma estructurada.\r\nFomento de la Creatividad: Diseñar prototipos originales que vayan más allá de los manuales de instrucciones.\r\nHabilidades Blandas (Soft Skills): Fortalecer la paciencia, el trabajo en equipo y la tolerancia a la frustración mediante el ensayo y error.\r\nAlfabetización Digital: Introducir lenguajes de programación modernos (Scratch y Python) de manera divertida.', 8, NULL, 220000.00, 'mensual', 7, 1, 0, '2026-04-03 11:25:21', '2026-04-03 11:25:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `curso_galeria`
--

CREATE TABLE `curso_galeria` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `imagen` varchar(255) NOT NULL COMMENT 'Ruta en /uploads/cursos/galeria/',
  `caption` varchar(200) DEFAULT NULL,
  `orden` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `curso_galeria`
--

INSERT INTO `curso_galeria` (`id`, `curso_id`, `imagen`, `caption`, `orden`, `created_at`) VALUES
(39, 2, 'gal_2_1774989331_1.jpeg', NULL, 2, '2026-03-31 15:35:31'),
(44, 3, 'gal_3_1774993447_0.webp', NULL, 1, '2026-03-31 16:44:07'),
(45, 3, 'gal_3_1774993447_1.webp', NULL, 2, '2026-03-31 16:44:07'),
(46, 3, 'gal_3_1774993447_2.jpeg', NULL, 3, '2026-03-31 16:44:07'),
(47, 2, 'gal_2_1774993477_0.jpeg', NULL, 4, '2026-03-31 16:44:37'),
(49, 2, 'gal_2_1774993477_2.jpg', NULL, 6, '2026-03-31 16:44:37'),
(50, 1, 'gal_1_1774993529_0.jpg', NULL, 1, '2026-03-31 16:45:29'),
(51, 1, 'gal_1_1774993529_1.jpg', NULL, 2, '2026-03-31 16:45:29'),
(52, 1, 'gal_1_1774993529_2.webp', NULL, 3, '2026-03-31 16:45:29'),
(53, 4, 'gal_4_1775191198_0.jpg', NULL, 1, '2026-04-02 23:39:58'),
(54, 4, 'gal_4_1775191198_1.png', NULL, 2, '2026-04-02 23:39:58'),
(55, 4, 'gal_4_1775191198_2.png', NULL, 3, '2026-04-02 23:39:58'),
(56, 4, 'gal_4_1775191198_3.jpg', NULL, 4, '2026-04-02 23:39:58'),
(57, 5, 'gal_5_1775233521_0.webp', NULL, 1, '2026-04-03 11:25:21'),
(58, 5, 'gal_5_1775233521_1.jpeg', NULL, 2, '2026-04-03 11:25:21'),
(59, 5, 'gal_5_1775233521_2.jpeg', NULL, 3, '2026-04-03 11:25:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `curso_materiales`
--

CREATE TABLE `curso_materiales` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `kit_referencia` varchar(100) DEFAULT NULL COMMENT 'Ej: Ecua-InnTech-03, Kuntur-02',
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `curso_materiales`
--

INSERT INTO `curso_materiales` (`id`, `curso_id`, `nombre`, `cantidad`, `kit_referencia`, `observaciones`) VALUES
(23, 3, 'LEGO WeDo', 10, 'LEGO WeDo', NULL),
(25, 2, 'LEGO Spyke', 6, 'LEGO Spyke Education', NULL),
(27, 1, 'LEGO BOOST Kit', 10, '', NULL),
(33, 5, 'LEGO Inventor Mindstorm', 7, 'LEGO Inventor Mindstorm', NULL),
(34, 4, 'LEGO Mindstorm Ev3', 6, '', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `curso_modulos`
--

CREATE TABLE `curso_modulos` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `curso_modulos`
--

INSERT INTO `curso_modulos` (`id`, `curso_id`, `nombre`, `descripcion`, `orden`) VALUES
(25, 3, 'Módulo 1: \"El Despertar de las Máquinas\"', 'Proyecto: El Ventilador Inteligente.\r\nQué aprenden: Introducción al motor y al Smarthub. Aprenden a controlar la velocidad y dirección del giro.', 1),
(27, 2, 'Módulo 1: El Despertar del Inventor (Fundamentos)', 'Sesión 1: Introducción al Hub y el entorno SPIKE. Mi primera animación en la matriz de luces.\r\nSesión 2: Movimiento básico: Motores y dirección. ¡Hagamos que el robot camine!\r\nSesión 3: El baile de los engranajes: Velocidad vs. Fuerza.', 1),
(29, 1, 'Módulo 1: El Despertar de las Máquinas (Introducción)', 'Conocemos el \"cerebro\" (Hub) y cómo los sensores ven el mundo. Construcción de modelos básicos para entender el movimiento.', 1),
(37, 5, 'Módulo 1: Fundamentos de Ingeniería y Mecánica', 'Enfoque: Palancas, engranajes y poleas.\r\nReto: Construir estructuras estables y entender cómo se transmite el movimiento antes de añadir \"cerebro\" al robot.', 1),
(38, 5, 'Módulo 2: Código en Movimiento (Programación Bloque a Bloque)', 'Enfoque: Introducción al Hub Inteligente y sensores (color, distancia y giroscopio).\r\nReto: Programar a un robot para que navegue un laberinto de forma autónoma usando lógica condicional.', 2),
(39, 5, 'Módulo 3: Inteligencia y Reacción', 'Enfoque: Interacción avanzada.\r\nReto: Crear modelos que reaccionen al entorno (robots que detectan obstáculos o que interactúan con la voz y el sonido).', 3),
(40, 4, 'Módulo 1: El Despertar del Robot (Introducción)', 'Conociendo el Bloque Inteligente EV3.\r\nPrimeros pasos: Motores y desplazamientos básicos (rectas, giros y círculos).', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docente_grupos`
--

CREATE TABLE `docente_grupos` (
  `id` int(10) UNSIGNED NOT NULL,
  `docente_id` int(10) UNSIGNED NOT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `docente_grupos`
--

INSERT INTO `docente_grupos` (`id`, `docente_id`, `grupo_id`) VALUES
(3, 11, 1),
(5, 11, 8),
(7, 13, 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ec_asignaciones`
--

CREATE TABLE `ec_asignaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `sesion_id` int(10) UNSIGNED NOT NULL,
  `tallerista_id` int(10) UNSIGNED NOT NULL,
  `rol` enum('principal','apoyo') NOT NULL DEFAULT 'principal',
  `confirmado` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si el tallerista confirmo asistencia',
  `notas` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ec_asignaciones`
--

INSERT INTO `ec_asignaciones` (`id`, `sesion_id`, `tallerista_id`, `rol`, `confirmado`, `notas`, `created_at`) VALUES
(1, 12, 13, 'principal', 0, NULL, '2026-04-22 20:37:03'),
(2, 12, 11, 'apoyo', 0, NULL, '2026-04-22 20:37:03'),
(3, 13, 13, 'principal', 0, NULL, '2026-04-22 20:45:46'),
(4, 13, 11, 'apoyo', 0, NULL, '2026-04-22 20:45:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ec_asistencia`
--

CREATE TABLE `ec_asistencia` (
  `id` int(10) UNSIGNED NOT NULL,
  `sesion_id` int(10) UNSIGNED NOT NULL,
  `estudiante_id` int(10) UNSIGNED NOT NULL,
  `estado` enum('presente','tarde','ausente','excusa') NOT NULL DEFAULT 'ausente',
  `observacion` varchar(255) DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ec_clientes`
--

CREATE TABLE `ec_clientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('colegio','empresa','institucion','otro') NOT NULL DEFAULT 'colegio',
  `nombre` varchar(200) NOT NULL,
  `nit` varchar(30) DEFAULT NULL COMMENT 'NIT o identificacion fiscal',
  `razon_social` varchar(200) DEFAULT NULL COMMENT 'Si difiere del nombre comercial',
  `ciudad` varchar(100) DEFAULT NULL,
  `direccion` varchar(250) DEFAULT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `sitio_web` varchar(200) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL COMMENT 'Ruta en /uploads/ec_clientes/',
  `contacto_nombre` varchar(150) DEFAULT NULL COMMENT 'Contacto principal rectoria coordinacion',
  `contacto_cargo` varchar(100) DEFAULT NULL,
  `contacto_telefono` varchar(30) DEFAULT NULL,
  `contacto_email` varchar(150) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ec_clientes`
--

INSERT INTO `ec_clientes` (`id`, `tipo`, `nombre`, `nit`, `razon_social`, `ciudad`, `direccion`, `barrio`, `latitud`, `longitud`, `telefono`, `email`, `sitio_web`, `logo`, `contacto_nombre`, `contacto_cargo`, `contacto_telefono`, `contacto_email`, `notas`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'colegio', 'Colegio Calatrava', '901066044-6.', 'Educalatrava S.A.S', 'Bogotá, D.c.', 'Carrera 80 # 156-95.', 'El bosque - Suba', 4.7519782, -74.0747725, '318 813 6751', 'info@colegiocalatrava.edu.co', 'www.colegiocalatrava.edu.co', NULL, 'Director de Curricular', 'Director de curricular', '12345678', 'curricular@colegiocalatrava.edu.co', '', 1, '2026-04-22 18:32:28', '2026-04-22 20:41:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ec_contratos`
--

CREATE TABLE `ec_contratos` (
  `id` int(10) UNSIGNED NOT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(30) DEFAULT NULL COMMENT 'Codigo interno legible ej EC-2026-001',
  `nombre` varchar(200) NOT NULL COMMENT 'Ej Robotica 2026-1 Gimnasio Moderno',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `tipo_duracion` enum('mensual','bimestral','trimestral','semestral','anual','personalizado') NOT NULL DEFAULT 'semestral',
  `valor_total` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor total pactado del contrato',
  `condiciones_pago` varchar(200) DEFAULT NULL COMMENT 'Ej pago mensual contra factura',
  `estado` enum('borrador','vigente','suspendido','finalizado','cancelado') NOT NULL DEFAULT 'borrador',
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ec_contratos`
--

INSERT INTO `ec_contratos` (`id`, `cliente_id`, `codigo`, `nombre`, `fecha_inicio`, `fecha_fin`, `tipo_duracion`, `valor_total`, `condiciones_pago`, `estado`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, 1, 'EC-2026-001', 'Extracurricular Colegio Calatrava - ROBOTSchool', '2026-01-17', '2026-07-22', 'semestral', 9999999999.99, 'Pago mensual 4 sesiones', 'vigente', '', '2026-04-22 18:34:34', '2026-04-22 20:02:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ec_desplazamientos_cache`
--

CREATE TABLE `ec_desplazamientos_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `origen_lat` decimal(10,7) NOT NULL,
  `origen_lng` decimal(10,7) NOT NULL,
  `destino_lat` decimal(10,7) NOT NULL,
  `destino_lng` decimal(10,7) NOT NULL,
  `distancia_km` decimal(8,2) NOT NULL COMMENT 'Haversine o API real',
  `duracion_min` int(10) UNSIGNED NOT NULL COMMENT 'Estimacion en minutos',
  `origen_nombre` varchar(200) DEFAULT NULL,
  `destino_nombre` varchar(200) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ec_estudiantes`
--

CREATE TABLE `ec_estudiantes` (
  `id` int(10) UNSIGNED NOT NULL,
  `programa_id` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `grado` varchar(30) DEFAULT NULL,
  `edad` tinyint(3) UNSIGNED DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL COMMENT 'Desde cuando este nino participa en el programa',
  `documento` varchar(30) DEFAULT NULL COMMENT 'Opcional',
  `observaciones` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ec_evaluaciones`
--

CREATE TABLE `ec_evaluaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `estudiante_id` int(10) UNSIGNED NOT NULL,
  `rubrica_id` int(10) UNSIGNED NOT NULL COMMENT 'FK a rubricas RSAL',
  `docente_id` int(10) UNSIGNED NOT NULL COMMENT 'Tallerista que evalua',
  `fecha` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ec_evaluacion_detalle`
--

CREATE TABLE `ec_evaluacion_detalle` (
  `id` int(10) UNSIGNED NOT NULL,
  `evaluacion_id` int(10) UNSIGNED NOT NULL,
  `criterio_id` int(10) UNSIGNED NOT NULL COMMENT 'FK a rubrica_criterios',
  `puntaje` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ec_programas`
--

CREATE TABLE `ec_programas` (
  `id` int(10) UNSIGNED NOT NULL,
  `contrato_id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK a cursos RSAL contenido pedagogico reutilizado',
  `nombre` varchar(200) NOT NULL COMMENT 'Ej LEGO SPIKE 3ero y 4to',
  `equipos_kit` varchar(200) DEFAULT NULL COMMENT 'Ej LEGO SPIKE Prime Arduino UNO',
  `grado_desde` varchar(30) DEFAULT NULL COMMENT 'Ej 3o 1ro bachillerato',
  `grado_hasta` varchar(30) DEFAULT NULL,
  `edad_min` tinyint(3) UNSIGNED DEFAULT NULL,
  `edad_max` tinyint(3) UNSIGNED DEFAULT NULL,
  `cantidad_ninos` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Numero aproximado de estudiantes',
  `minimo_ninos` int(10) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Minimo viable bajo este numero se alerta pero no se bloquea',
  `dia_semana` enum('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `total_sesiones` int(10) UNSIGNED NOT NULL DEFAULT 4 COMMENT 'Fijo en 4 por paquete 1 sesion semanal',
  `valor_por_nino` decimal(10,2) NOT NULL DEFAULT 120000.00 COMMENT 'Tarifa por nino paquete de 4 sesiones',
  `color` varchar(7) DEFAULT '#7c3aed' COMMENT 'Color para calendario visual',
  `estado` enum('planeado','en_curso','finalizado','suspendido') NOT NULL DEFAULT 'planeado',
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ec_programas`
--

INSERT INTO `ec_programas` (`id`, `contrato_id`, `curso_id`, `nombre`, `equipos_kit`, `grado_desde`, `grado_hasta`, `edad_min`, `edad_max`, `cantidad_ninos`, `minimo_ninos`, `dia_semana`, `hora_inicio`, `hora_fin`, `total_sesiones`, `valor_por_nino`, `color`, `estado`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'LEGO BOOST Robótica', 'LEGO BOOST', '', '', 6, 10, 12, 10, 'viernes', '13:00:00', '14:00:00', 16, 126000.00, '#7c3aed', 'en_curso', '', '2026-04-22 18:41:25', '2026-04-22 20:44:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ec_sesiones`
--

CREATE TABLE `ec_sesiones` (
  `id` int(10) UNSIGNED NOT NULL,
  `programa_id` int(10) UNSIGNED NOT NULL,
  `numero_sesion` int(10) UNSIGNED NOT NULL COMMENT 'Secuencia 1 2 3 ... en el programa',
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `tema_planeado` varchar(200) DEFAULT NULL,
  `estado` enum('programada','dictada','fallida_justificada','fallida_no_justificada','recuperada','cancelada') NOT NULL DEFAULT 'programada',
  `motivo_falla` varchar(255) DEFAULT NULL,
  `sesion_original_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Si es recuperacion referencia la sesion fallida',
  `observaciones` text DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED DEFAULT NULL COMMENT 'Tallerista que cerro la sesion',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ec_sesiones`
--

INSERT INTO `ec_sesiones` (`id`, `programa_id`, `numero_sesion`, `fecha`, `hora_inicio`, `hora_fin`, `tema_planeado`, `estado`, `motivo_falla`, `sesion_original_id`, `observaciones`, `registrado_por`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-01-23', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 19:56:34', '2026-04-22 19:56:34'),
(2, 1, 2, '2026-01-30', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 19:56:34', '2026-04-22 19:56:34'),
(3, 1, 3, '2026-02-06', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 19:56:34', '2026-04-22 19:56:34'),
(4, 1, 4, '2026-02-13', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 19:56:34', '2026-04-22 19:56:34'),
(5, 1, 5, '2026-02-20', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:09:45', '2026-04-22 20:09:45'),
(6, 1, 6, '2026-02-27', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:09:45', '2026-04-22 20:09:45'),
(7, 1, 7, '2026-03-06', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:09:45', '2026-04-22 20:09:45'),
(8, 1, 8, '2026-03-13', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:09:45', '2026-04-22 20:09:45'),
(9, 1, 9, '2026-03-20', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:10:06', '2026-04-22 20:10:06'),
(10, 1, 10, '2026-03-27', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:10:06', '2026-04-22 20:10:06'),
(11, 1, 11, '2026-04-03', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:10:06', '2026-04-22 20:10:06'),
(12, 1, 12, '2026-04-10', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:10:06', '2026-04-22 20:10:06'),
(13, 1, 13, '2026-04-17', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:44:55', '2026-04-22 20:44:55'),
(14, 1, 14, '2026-04-24', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:44:55', '2026-04-22 20:44:55'),
(15, 1, 15, '2026-05-01', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:44:55', '2026-04-22 20:44:55'),
(16, 1, 16, '2026-05-08', '13:00:00', '14:00:00', NULL, 'programada', NULL, NULL, NULL, NULL, '2026-04-22 20:44:55', '2026-04-22 20:44:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `id` int(10) UNSIGNED NOT NULL,
  `sede_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL COMMENT 'Ej: LEGO Spike Prime',
  `descripcion` varchar(255) DEFAULT NULL,
  `cantidad_total` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `equipos`
--

INSERT INTO `equipos` (`id`, `sede_id`, `nombre`, `descripcion`, `cantidad_total`, `activo`, `created_at`) VALUES
(1, 1, 'LEGO BOOST Robótica', 'LEGO BOOST Robótica Exploración', 30, 1, '2026-03-31 12:41:57'),
(2, 1, 'LEGO SPYKE Robótica Intermedia', '', 6, 1, '2026-03-31 12:43:39'),
(3, 1, 'LEGO Wedo', 'LEGO® Education Para exploracion', 7, 1, '2026-03-31 14:16:32'),
(4, 2, 'Lego Ev3 Mindstorms', 'LEGO Mindstorm EV3 robotica intermedia', 5, 1, '2026-04-02 23:33:06'),
(5, 2, 'MBot 1', 'Kit de robotica programación Makeblock', 8, 1, '2026-04-02 23:34:16'),
(6, 2, 'LEGO BOOST Robótica', 'LEGO BOOST Robótica Exploración', 11, 1, '2026-04-02 23:50:13'),
(7, 1, 'LEGO Inventor Mindstorm', 'LEGO Inventor, kit de robótica intermedio', 6, 1, '2026-04-03 11:17:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id` int(10) UNSIGNED NOT NULL,
  `padre_id` int(10) UNSIGNED NOT NULL,
  `sede_id` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `tipo_doc` enum('TI','RC','PP','CE') NOT NULL DEFAULT 'TI',
  `numero_doc` varchar(30) DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `genero` enum('masculino','femenino','otro','prefiero_no_decir') NOT NULL DEFAULT 'prefiero_no_decir',
  `colegio` varchar(150) DEFAULT NULL,
  `grado` varchar(30) DEFAULT NULL,
  `eps` varchar(100) DEFAULT NULL,
  `grupo_sanguineo` varchar(10) DEFAULT NULL,
  `seguro_estudiantil` varchar(150) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL COMMENT 'Ruta relativa en /uploads/estudiantes/',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id`, `padre_id`, `sede_id`, `nombre_completo`, `tipo_doc`, `numero_doc`, `fecha_nacimiento`, `genero`, `colegio`, `grado`, `eps`, `grupo_sanguineo`, `seguro_estudiantil`, `alergias`, `observaciones`, `avatar`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Juanita María Puchana Borda', 'TI', '101645433', '2011-05-06', 'femenino', 'Colegio Bethlemitas Chapinero', '9°', 'Compensar', '', '', 'Ninguna', '', 'est_1775005316_348.jpg', 1, '2026-03-29 23:25:44', '2026-03-31 20:01:56'),
(2, 2, 1, 'Santiago José Puchana Borda', 'TI', '101654321', '2013-07-11', 'masculino', 'Colegio Bethlemitas Chapinero', '11°', 'Compensar', '', '', 'Carranchin', '', 'est_1775005823_588.webp', 1, '2026-03-31 20:06:01', '2026-03-31 20:10:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones`
--

CREATE TABLE `evaluaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `rubrica_id` int(10) UNSIGNED NOT NULL,
  `docente_id` int(10) UNSIGNED NOT NULL COMMENT 'usuario_id del docente o admin',
  `fecha` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `evaluaciones`
--

INSERT INTO `evaluaciones` (`id`, `matricula_id`, `rubrica_id`, `docente_id`, `fecha`, `observaciones`, `created_at`) VALUES
(1, 1, 1, 1, '2026-04-25', 'Super pila', '2026-03-31 12:25:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluacion_detalle`
--

CREATE TABLE `evaluacion_detalle` (
  `id` int(10) UNSIGNED NOT NULL,
  `evaluacion_id` int(10) UNSIGNED NOT NULL,
  `criterio_id` int(10) UNSIGNED NOT NULL,
  `puntaje` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `observacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `evaluacion_detalle`
--

INSERT INTO `evaluacion_detalle` (`id`, `evaluacion_id`, `criterio_id`, `puntaje`, `observacion`) VALUES
(1, 1, 1, 3, ''),
(2, 1, 2, 4, ''),
(3, 1, 3, 4, ''),
(4, 1, 4, 4, ''),
(5, 1, 5, 1, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `sede_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Ej: Grupo Sábado S1',
  `dia_semana` enum('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `modalidad` enum('presencial','virtual','hibrida') NOT NULL DEFAULT 'presencial',
  `cupo_equipos` int(10) UNSIGNED DEFAULT NULL COMMENT 'Calculado por equipos asignados',
  `cupo_aula` int(10) UNSIGNED DEFAULT NULL COMMENT 'Capacidad física del aula',
  `cupo_admin` int(10) UNSIGNED DEFAULT NULL COMMENT 'Límite manual del admin',
  `cupo_real` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'MIN de los tres anteriores — calculado',
  `periodo` varchar(20) NOT NULL COMMENT 'Ej: 2026-1',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id`, `curso_id`, `sede_id`, `nombre`, `dia_semana`, `hora_inicio`, `hora_fin`, `modalidad`, `cupo_equipos`, `cupo_aula`, `cupo_admin`, `cupo_real`, `periodo`, `fecha_inicio`, `fecha_fin`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'LEGO BOOST Robótica', 'sabado', '08:00:00', '10:00:00', 'presencial', 10, 10, 10, 10, '2026-4', '2026-04-11', '2026-04-25', 1, '2026-03-29 22:41:36', '2026-03-31 19:59:50'),
(2, 1, 1, 'LEGO BOOST Robótica', 'sabado', '10:30:00', '12:30:00', 'presencial', 10, 10, 10, 10, '2026-4', '2026-04-11', '2026-04-25', 1, '2026-03-31 11:53:12', '2026-03-31 19:56:28'),
(3, 1, 1, 'LEGO BOOST Robótica', 'sabado', '13:00:00', '15:00:00', 'presencial', 10, 10, 10, 10, '2026-4', '2026-04-04', '2026-04-25', 1, '2026-03-31 11:54:20', '2026-03-31 19:56:41'),
(4, 2, 1, 'Lego Spyke Prime', 'sabado', '08:00:00', '10:00:00', 'presencial', 6, 7, 6, 6, '2026-4', '2026-04-04', '2026-04-25', 1, '2026-03-31 12:14:43', '2026-03-31 20:01:00'),
(5, 2, 1, 'LEGO SPYKE Robótica', 'sabado', '10:30:00', '12:30:00', 'presencial', NULL, 6, 6, 6, '2026-4', '2026-04-04', '2026-04-25', 1, '2026-03-31 12:17:46', '2026-03-31 12:17:46'),
(6, 2, 1, 'LEGO SPYKE Robótica', 'sabado', '13:00:00', '15:00:00', 'presencial', NULL, 6, 6, 6, '2026-4', '2026-04-04', '2026-04-25', 1, '2026-03-31 12:18:49', '2026-03-31 12:19:28'),
(7, 3, 1, 'LEGo Wedo', 'sabado', '08:00:00', '10:00:00', 'presencial', 1, 7, 7, 1, '2026-4', '2026-04-04', '2026-04-25', 1, '2026-03-31 14:15:01', '2026-03-31 14:17:17'),
(8, 4, 2, 'Lego Ev3 Mindstorms', 'sabado', '13:00:00', '15:00:00', 'presencial', 5, NULL, NULL, 5, '2026-4', '2026-04-11', '2026-04-25', 1, '2026-04-02 23:42:22', '2026-04-02 23:43:31'),
(9, 1, 2, 'LEGO BOOST Robótica', 'sabado', '10:30:00', '12:30:00', 'presencial', 11, 7, 7, 7, '2026-4', '2026-04-11', '2026-04-25', 1, '2026-04-02 23:51:42', '2026-04-03 11:15:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupo_equipos`
--

CREATE TABLE `grupo_equipos` (
  `id` int(10) UNSIGNED NOT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL,
  `equipo_id` int(10) UNSIGNED NOT NULL,
  `cantidad_requerida` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grupo_equipos`
--

INSERT INTO `grupo_equipos` (`id`, `grupo_id`, `equipo_id`, `cantidad_requerida`) VALUES
(2, 7, 3, 1),
(7, 2, 1, 10),
(8, 3, 1, 10),
(9, 1, 1, 10),
(10, 4, 2, 6),
(11, 8, 4, 5),
(13, 9, 6, 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matriculas`
--

CREATE TABLE `matriculas` (
  `id` int(10) UNSIGNED NOT NULL,
  `estudiante_id` int(10) UNSIGNED NOT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL,
  `sede_id` int(10) UNSIGNED NOT NULL,
  `estado` enum('pre_inscrito','activa','retirada','finalizada','suspendida') NOT NULL DEFAULT 'pre_inscrito',
  `periodo` varchar(20) NOT NULL COMMENT 'Ej: 2026-1, 2026-2',
  `fecha_matricula` datetime NOT NULL DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `matriculas`
--

INSERT INTO `matriculas` (`id`, `estudiante_id`, `grupo_id`, `sede_id`, `estado`, `periodo`, `fecha_matricula`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'activa', '2026-4', '2026-03-29 23:25:44', '', '2026-03-29 23:25:44', '2026-03-31 15:38:15'),
(2, 2, 5, 1, 'activa', '2026-1', '2026-03-31 20:06:01', '', '2026-03-31 20:06:01', '2026-03-31 20:08:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matricula_historial`
--

CREATE TABLE `matricula_historial` (
  `id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL COMMENT 'Matricula original (queda finalizada)',
  `grupo_id_anterior` int(10) UNSIGNED NOT NULL,
  `grupo_id_nuevo` int(10) UNSIGNED NOT NULL,
  `motivo` varchar(255) DEFAULT 'Avance de modulo',
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Quien registro el avance',
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `observaciones`
--

CREATE TABLE `observaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = observacion general del grupo',
  `tipo` enum('general','estudiante') NOT NULL DEFAULT 'estudiante',
  `fecha` date NOT NULL,
  `texto` text NOT NULL,
  `visible_padre` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = padre puede verla en su portal',
  `registrado_por` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `observaciones`
--

INSERT INTO `observaciones` (`id`, `grupo_id`, `matricula_id`, `tipo`, `fecha`, `texto`, `visible_padre`, `registrado_por`, `created_at`) VALUES
(1, 1, 1, 'estudiante', '2026-04-02', 'La niña trabajó hoy perfectamente', 1, 11, '2026-04-02 16:41:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `padres`
--

CREATE TABLE `padres` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `tipo_doc` enum('CC','CE','PP','NIT','TI') NOT NULL DEFAULT 'CC',
  `numero_doc` varchar(30) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `telefono_alt` varchar(20) DEFAULT NULL,
  `email` varchar(120) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `ocupacion` varchar(100) DEFAULT NULL,
  `acepta_datos` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Política de tratamiento de datos',
  `acepta_imagenes` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Autorización uso de imágenes',
  `fecha_aceptacion` datetime DEFAULT NULL,
  `ip_aceptacion` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `padres`
--

INSERT INTO `padres` (`id`, `usuario_id`, `nombre_completo`, `tipo_doc`, `numero_doc`, `telefono`, `telefono_alt`, `email`, `direccion`, `ocupacion`, `acepta_datos`, `acepta_imagenes`, `fecha_aceptacion`, `ip_aceptacion`, `created_at`) VALUES
(1, 5, 'Francisco Javier Puchana Hernández', 'CC', '79641297', '35059798434', NULL, 'fjpuchana@gmail.com', NULL, NULL, 1, 1, '2026-03-29 23:25:44', '::1', '2026-03-29 23:25:44'),
(2, 12, 'Claudia Liliana Borda Rodriguez', 'CC', '52214647', '3183403773', NULL, 'cborda18@hotmail.com', NULL, NULL, 1, 1, '2026-03-31 20:06:01', '::1', '2026-03-31 20:06:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `padre_id` int(10) UNSIGNED NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `valor_pagado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','parcial','pagado','vencido','exonerado') NOT NULL DEFAULT 'pendiente',
  `fecha_limite` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `matricula_id`, `padre_id`, `valor_total`, `valor_pagado`, `estado`, `fecha_limite`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 200000.00, 80001.00, 'parcial', '2026-04-28', NULL, '2026-03-29 23:25:44', '2026-03-31 14:38:34'),
(2, 2, 2, 210000.00, 210000.00, 'pagado', '2026-04-30', NULL, '2026-03-31 20:06:01', '2026-03-31 20:11:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_abonos`
--

CREATE TABLE `pagos_abonos` (
  `id` int(10) UNSIGNED NOT NULL,
  `pago_id` int(10) UNSIGNED NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `medio_pago` enum('efectivo','transferencia','nequi','daviplata','pse','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
  `comprobante` varchar(255) DEFAULT NULL COMMENT 'Ruta /uploads/comprobantes/ o número de ref.',
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `registrado_por` int(10) UNSIGNED NOT NULL COMMENT 'usuario_id del admin que registra',
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos_abonos`
--

INSERT INTO `pagos_abonos` (`id`, `pago_id`, `valor`, `medio_pago`, `comprobante`, `fecha`, `registrado_por`, `observaciones`) VALUES
(1, 1, 80001.00, 'efectivo', '0001', '2026-03-31 14:38:34', 1, 'abono'),
(2, 2, 209001.00, 'efectivo', '0002', '2026-03-31 20:11:19', 1, 'Pago Total'),
(3, 2, 210001.00, 'efectivo', '', '2026-03-31 20:11:56', 1, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rubricas`
--

CREATE TABLE `rubricas` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `periodo` varchar(20) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rubricas`
--

INSERT INTO `rubricas` (`id`, `curso_id`, `nombre`, `descripcion`, `periodo`, `activa`, `created_at`) VALUES
(1, 1, 'LEGO BOOST Robótica', 'Desempeño durante el primer modulo', '2026-4', 1, '2026-03-31 12:21:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rubrica_criterios`
--

CREATE TABLE `rubrica_criterios` (
  `id` int(10) UNSIGNED NOT NULL,
  `rubrica_id` int(10) UNSIGNED NOT NULL,
  `criterio` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `puntaje_max` int(10) UNSIGNED NOT NULL DEFAULT 5,
  `orden` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rubrica_criterios`
--

INSERT INTO `rubrica_criterios` (`id`, `rubrica_id`, `criterio`, `descripcion`, `puntaje_max`, `orden`) VALUES
(1, 1, 'Participación y actitud', 'Disposición, atención y participación activa durante las sesiones', 5, 1),
(2, 1, 'Comprensión del tema', 'Entiende los conceptos y principios trabajados en clase', 5, 2),
(3, 1, 'Construcción y prototipado', 'Habilidad para construir, armar y ajustar prototipos robóticos', 5, 3),
(4, 1, 'Programación y lógica', 'Capacidad para programar y depurar el comportamiento del robot', 5, 4),
(5, 1, 'Creatividad e innovación', 'Propone soluciones originales y mejoras a los diseños', 5, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `ciudad` varchar(80) NOT NULL,
  `direccion` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sedes`
--

INSERT INTO `sedes` (`id`, `nombre`, `ciudad`, `direccion`, `telefono`, `email`, `activa`, `created_at`) VALUES
(1, 'Sede 75 San Felipe', 'Bogotá', 'Calle 75 # San Felipe, Bogotá', '3186541859', 'robotschoolcol@gmail.com', 1, '2026-03-29 21:32:35'),
(2, 'Sede Norte 136', 'Bogotá', 'Calle 136 Norte, Bogotá', NULL, NULL, 1, '2026-03-29 21:32:35'),
(3, 'Sede Cali', 'Cali', 'Cali, Valle del Cauca', NULL, NULL, 1, '2026-03-29 21:32:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id` int(10) UNSIGNED NOT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL COMMENT 'Fecha real de la clase',
  `tema` varchar(200) DEFAULT NULL COMMENT 'Tema o actividad de la sesion',
  `observaciones` text DEFAULT NULL,
  `creado_por` int(10) UNSIGNED NOT NULL COMMENT 'usuario_id quien crea la sesion',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`id`, `grupo_id`, `fecha`, `tema`, `observaciones`, `creado_por`, `created_at`) VALUES
(1, 1, '2026-04-20', NULL, NULL, 11, '2026-04-20 19:11:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temas`
--

CREATE TABLE `temas` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `objetivos` text DEFAULT NULL COMMENT 'Objetivos pedagogicos del tema',
  `orden` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `sede_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = admin general',
  `nombre` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin_general','admin_sede','coordinador_pedagogico','docente','padre') NOT NULL DEFAULT 'padre',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `sede_id`, `nombre`, `email`, `password_hash`, `rol`, `activo`, `ultimo_login`, `created_at`) VALUES
(1, NULL, 'Administrador General', 'admin@robotschool.com.co', '$2y$10$JoByX6T8Y7khRUI5O/bxt.4LG6/pGBCFwEBQ.CTVQkZyU.jwIOGJK', 'admin_general', 1, '2026-04-22 19:43:54', '2026-03-29 21:32:35'),
(2, 1, 'Admin Sede 75', 'sede75@robotschool.com.co', '$2y$10$7zr3YteglcAG99mCVcgQtuy79Ui7PTzbsuJzhUbypU7dWVRScnORG', 'admin_sede', 1, '2026-04-20 18:41:11', '2026-03-29 21:32:35'),
(3, 2, 'Admin Sede Norte', 'sedenorte@robotschool.com.co', '$2y$10$7zr3YteglcAG99mCVcgQtuy79Ui7PTzbsuJzhUbypU7dWVRScnORG', 'admin_sede', 1, '2026-04-05 21:32:19', '2026-03-29 21:32:35'),
(4, 3, 'Admin Sede Cali', 'sedecali@robotschool.com.co', '$2y$10$7zr3YteglcAG99mCVcgQtuy79Ui7PTzbsuJzhUbypU7dWVRScnORG', 'admin_sede', 1, NULL, '2026-03-29 21:32:35'),
(5, NULL, 'Francisco Javier Puchana Hernández', 'fjpuchana@gmail.com', '$2y$10$zGykKdsZ2csd8QuXSxpIR.O3NgwXKovNKXmze1xscNhBrmZATm/8e', 'padre', 1, '2026-04-05 21:16:32', '2026-03-29 23:25:44'),
(10, NULL, 'Jose Alberto Molina Maturana', 'jamolina@robotschool.com.co', '$2y$10$K0QauMDbL3lvzXypAtOyAugDdJXSUa5/AKRLz7fvCMIIwHmzhjqJq', 'coordinador_pedagogico', 1, '2026-04-22 20:34:31', '2026-03-31 16:38:10'),
(11, 1, 'Tomás Esteban Puchana Borda', 'tepuchana@robotschool.com.co', '$2y$10$3BKsjMEMF.GEOHDkRaEqQ./ODb5WCTY8hOfmzYCZpf8QSPLJIAJ56', 'docente', 1, '2026-04-22 19:42:09', '2026-03-31 16:38:54'),
(12, NULL, 'Claudia Liliana Borda Rodriguez', 'cborda18@hotmail.com', '$2y$10$3Ja9Hd6RskXpjHKiuYK52uO7lgQLBwYxY8YQL0GX6nkoe3pncJ/oC', 'padre', 1, '2026-03-31 20:07:30', '2026-03-31 20:06:01'),
(13, NULL, 'Hugo Nicolás Plazas', 'hnplazas@robotschool.com.co', '$2y$10$.mU4qwtWuUxabe4Q8i0BNuCgDIEfK0JDsJdpKseH8z6YRXfGc8CCS', 'docente', 1, '2026-04-20 18:41:50', '2026-04-02 23:48:46');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_actividad_tema` (`tema_id`,`orden`),
  ADD KEY `idx_actividad_rubrica` (`rubrica_id`);

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_asistencia` (`sesion_id`,`matricula_id`),
  ADD KEY `fk_asist_sesion` (`sesion_id`),
  ADD KEY `fk_asist_matricula` (`matricula_id`),
  ADD KEY `fk_asist_registrador` (`registrado_por`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `curso_galeria`
--
ALTER TABLE `curso_galeria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_galeria_curso` (`curso_id`);

--
-- Indices de la tabla `curso_materiales`
--
ALTER TABLE `curso_materiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_materiales_curso` (`curso_id`);

--
-- Indices de la tabla `curso_modulos`
--
ALTER TABLE `curso_modulos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_modulos_curso` (`curso_id`);

--
-- Indices de la tabla `docente_grupos`
--
ALTER TABLE `docente_grupos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_docente_grupo` (`docente_id`,`grupo_id`),
  ADD KEY `fk_dg_grupo` (`grupo_id`);

--
-- Indices de la tabla `ec_asignaciones`
--
ALTER TABLE `ec_asignaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ec_asig_sesion_tallerista` (`sesion_id`,`tallerista_id`),
  ADD KEY `idx_ec_asig_tallerista` (`tallerista_id`);

--
-- Indices de la tabla `ec_asistencia`
--
ALTER TABLE `ec_asistencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ec_asist` (`sesion_id`,`estudiante_id`),
  ADD KEY `idx_ec_asist_estudiante` (`estudiante_id`),
  ADD KEY `fk_ec_asist_registrador` (`registrado_por`);

--
-- Indices de la tabla `ec_clientes`
--
ALTER TABLE `ec_clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ec_cliente_tipo` (`tipo`,`activo`),
  ADD KEY `idx_ec_cliente_ciudad` (`ciudad`);

--
-- Indices de la tabla `ec_contratos`
--
ALTER TABLE `ec_contratos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ec_contrato_codigo` (`codigo`),
  ADD KEY `idx_ec_contrato_cliente` (`cliente_id`,`estado`);

--
-- Indices de la tabla `ec_desplazamientos_cache`
--
ALTER TABLE `ec_desplazamientos_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ec_desp_coords` (`origen_lat`,`origen_lng`,`destino_lat`,`destino_lng`);

--
-- Indices de la tabla `ec_estudiantes`
--
ALTER TABLE `ec_estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ec_estudiante_programa` (`programa_id`,`activo`);

--
-- Indices de la tabla `ec_evaluaciones`
--
ALTER TABLE `ec_evaluaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ec_eval_estudiante` (`estudiante_id`),
  ADD KEY `idx_ec_eval_rubrica` (`rubrica_id`),
  ADD KEY `fk_ec_eval_docente` (`docente_id`);

--
-- Indices de la tabla `ec_evaluacion_detalle`
--
ALTER TABLE `ec_evaluacion_detalle`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ec_eval_det` (`evaluacion_id`,`criterio_id`),
  ADD KEY `fk_ec_eval_det_criterio` (`criterio_id`);

--
-- Indices de la tabla `ec_programas`
--
ALTER TABLE `ec_programas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ec_programa_contrato` (`contrato_id`),
  ADD KEY `idx_ec_programa_curso` (`curso_id`);

--
-- Indices de la tabla `ec_sesiones`
--
ALTER TABLE `ec_sesiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ec_sesion_programa_numero` (`programa_id`,`numero_sesion`),
  ADD KEY `idx_ec_sesion_fecha` (`fecha`,`estado`),
  ADD KEY `idx_ec_sesion_original` (`sesion_original_id`),
  ADD KEY `fk_ec_sesion_registrador` (`registrado_por`);

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_equipos_sede` (`sede_id`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_estudiantes_padre` (`padre_id`),
  ADD KEY `fk_estudiantes_sede` (`sede_id`);

--
-- Indices de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_eval_matricula` (`matricula_id`),
  ADD KEY `fk_eval_rubrica` (`rubrica_id`),
  ADD KEY `fk_eval_docente` (`docente_id`);

--
-- Indices de la tabla `evaluacion_detalle`
--
ALTER TABLE `evaluacion_detalle`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_eval_criterio` (`evaluacion_id`,`criterio_id`),
  ADD KEY `fk_detalle_evaluacion` (`evaluacion_id`),
  ADD KEY `fk_detalle_criterio` (`criterio_id`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_grupos_curso` (`curso_id`),
  ADD KEY `fk_grupos_sede` (`sede_id`);

--
-- Indices de la tabla `grupo_equipos`
--
ALTER TABLE `grupo_equipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_grupo_equipo` (`grupo_id`,`equipo_id`),
  ADD KEY `fk_ge_grupo` (`grupo_id`),
  ADD KEY `fk_ge_equipo` (`equipo_id`);

--
-- Indices de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_matriculas_estudiante` (`estudiante_id`),
  ADD KEY `fk_matriculas_grupo` (`grupo_id`),
  ADD KEY `fk_matriculas_sede` (`sede_id`);

--
-- Indices de la tabla `matricula_historial`
--
ALTER TABLE `matricula_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mh_matricula` (`matricula_id`),
  ADD KEY `fk_mh_g_anterior` (`grupo_id_anterior`),
  ADD KEY `fk_mh_g_nuevo` (`grupo_id_nuevo`);

--
-- Indices de la tabla `observaciones`
--
ALTER TABLE `observaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_obs_grupo` (`grupo_id`),
  ADD KEY `fk_obs_matricula` (`matricula_id`),
  ADD KEY `fk_obs_registrador` (`registrado_por`);

--
-- Indices de la tabla `padres`
--
ALTER TABLE `padres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_padre_doc` (`numero_doc`),
  ADD KEY `fk_padres_usuario` (`usuario_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pagos_matricula` (`matricula_id`),
  ADD KEY `fk_pagos_padre` (`padre_id`);

--
-- Indices de la tabla `pagos_abonos`
--
ALTER TABLE `pagos_abonos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_abonos_pago` (`pago_id`),
  ADD KEY `fk_abonos_usuario` (`registrado_por`);

--
-- Indices de la tabla `rubricas`
--
ALTER TABLE `rubricas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rubricas_curso` (`curso_id`);

--
-- Indices de la tabla `rubrica_criterios`
--
ALTER TABLE `rubrica_criterios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_criterios_rubrica` (`rubrica_id`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sesion_grupo_fecha` (`grupo_id`,`fecha`),
  ADD KEY `fk_sesion_grupo` (`grupo_id`),
  ADD KEY `fk_sesion_creador` (`creado_por`);

--
-- Indices de la tabla `temas`
--
ALTER TABLE `temas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tema_curso` (`curso_id`,`orden`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD KEY `fk_usuarios_sede` (`sede_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades`
--
ALTER TABLE `actividades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `curso_galeria`
--
ALTER TABLE `curso_galeria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT de la tabla `curso_materiales`
--
ALTER TABLE `curso_materiales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `curso_modulos`
--
ALTER TABLE `curso_modulos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `docente_grupos`
--
ALTER TABLE `docente_grupos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `ec_asignaciones`
--
ALTER TABLE `ec_asignaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ec_asistencia`
--
ALTER TABLE `ec_asistencia`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ec_clientes`
--
ALTER TABLE `ec_clientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ec_contratos`
--
ALTER TABLE `ec_contratos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ec_desplazamientos_cache`
--
ALTER TABLE `ec_desplazamientos_cache`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ec_estudiantes`
--
ALTER TABLE `ec_estudiantes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ec_evaluaciones`
--
ALTER TABLE `ec_evaluaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ec_evaluacion_detalle`
--
ALTER TABLE `ec_evaluacion_detalle`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ec_programas`
--
ALTER TABLE `ec_programas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ec_sesiones`
--
ALTER TABLE `ec_sesiones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `evaluacion_detalle`
--
ALTER TABLE `evaluacion_detalle`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `grupo_equipos`
--
ALTER TABLE `grupo_equipos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `matriculas`
--
ALTER TABLE `matriculas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `matricula_historial`
--
ALTER TABLE `matricula_historial`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `observaciones`
--
ALTER TABLE `observaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `padres`
--
ALTER TABLE `padres`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pagos_abonos`
--
ALTER TABLE `pagos_abonos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `rubricas`
--
ALTER TABLE `rubricas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `rubrica_criterios`
--
ALTER TABLE `rubrica_criterios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `temas`
--
ALTER TABLE `temas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD CONSTRAINT `fk_actividad_rubrica` FOREIGN KEY (`rubrica_id`) REFERENCES `rubricas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_actividad_tema` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `fk_asist_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asist_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asist_sesion` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `curso_galeria`
--
ALTER TABLE `curso_galeria`
  ADD CONSTRAINT `fk_galeria_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `curso_materiales`
--
ALTER TABLE `curso_materiales`
  ADD CONSTRAINT `fk_materiales_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `curso_modulos`
--
ALTER TABLE `curso_modulos`
  ADD CONSTRAINT `fk_modulos_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `docente_grupos`
--
ALTER TABLE `docente_grupos`
  ADD CONSTRAINT `fk_dg_docente` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dg_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ec_asignaciones`
--
ALTER TABLE `ec_asignaciones`
  ADD CONSTRAINT `fk_ec_asig_sesion` FOREIGN KEY (`sesion_id`) REFERENCES `ec_sesiones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_asig_tallerista` FOREIGN KEY (`tallerista_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ec_asistencia`
--
ALTER TABLE `ec_asistencia`
  ADD CONSTRAINT `fk_ec_asist_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `ec_estudiantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_asist_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ec_asist_sesion` FOREIGN KEY (`sesion_id`) REFERENCES `ec_sesiones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ec_contratos`
--
ALTER TABLE `ec_contratos`
  ADD CONSTRAINT `fk_ec_contrato_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `ec_clientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ec_estudiantes`
--
ALTER TABLE `ec_estudiantes`
  ADD CONSTRAINT `fk_ec_estudiante_programa` FOREIGN KEY (`programa_id`) REFERENCES `ec_programas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ec_evaluaciones`
--
ALTER TABLE `ec_evaluaciones`
  ADD CONSTRAINT `fk_ec_eval_docente` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_eval_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `ec_estudiantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_eval_rubrica` FOREIGN KEY (`rubrica_id`) REFERENCES `rubricas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ec_evaluacion_detalle`
--
ALTER TABLE `ec_evaluacion_detalle`
  ADD CONSTRAINT `fk_ec_eval_det_criterio` FOREIGN KEY (`criterio_id`) REFERENCES `rubrica_criterios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_eval_det_eval` FOREIGN KEY (`evaluacion_id`) REFERENCES `ec_evaluaciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ec_programas`
--
ALTER TABLE `ec_programas`
  ADD CONSTRAINT `fk_ec_programa_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `ec_contratos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_programa_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `ec_sesiones`
--
ALTER TABLE `ec_sesiones`
  ADD CONSTRAINT `fk_ec_sesion_original` FOREIGN KEY (`sesion_original_id`) REFERENCES `ec_sesiones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ec_sesion_programa` FOREIGN KEY (`programa_id`) REFERENCES `ec_programas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_sesion_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD CONSTRAINT `fk_equipos_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `fk_estudiantes_padre` FOREIGN KEY (`padre_id`) REFERENCES `padres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_estudiantes_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  ADD CONSTRAINT `fk_eval_docente` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eval_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eval_rubrica` FOREIGN KEY (`rubrica_id`) REFERENCES `rubricas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `evaluacion_detalle`
--
ALTER TABLE `evaluacion_detalle`
  ADD CONSTRAINT `fk_detalle_criterio` FOREIGN KEY (`criterio_id`) REFERENCES `rubrica_criterios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_evaluacion` FOREIGN KEY (`evaluacion_id`) REFERENCES `evaluaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD CONSTRAINT `fk_grupos_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grupos_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `grupo_equipos`
--
ALTER TABLE `grupo_equipos`
  ADD CONSTRAINT `fk_ge_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ge_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `matriculas`
--
ALTER TABLE `matriculas`
  ADD CONSTRAINT `fk_matriculas_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matriculas_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matriculas_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `matricula_historial`
--
ALTER TABLE `matricula_historial`
  ADD CONSTRAINT `fk_mh_g_anterior` FOREIGN KEY (`grupo_id_anterior`) REFERENCES `grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mh_g_nuevo` FOREIGN KEY (`grupo_id_nuevo`) REFERENCES `grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mh_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `observaciones`
--
ALTER TABLE `observaciones`
  ADD CONSTRAINT `fk_obs_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_obs_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_obs_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `padres`
--
ALTER TABLE `padres`
  ADD CONSTRAINT `fk_padres_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pagos_padre` FOREIGN KEY (`padre_id`) REFERENCES `padres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_abonos`
--
ALTER TABLE `pagos_abonos`
  ADD CONSTRAINT `fk_abonos_pago` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_abonos_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `rubricas`
--
ALTER TABLE `rubricas`
  ADD CONSTRAINT `fk_rubricas_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rubrica_criterios`
--
ALTER TABLE `rubrica_criterios`
  ADD CONSTRAINT `fk_criterios_rubrica` FOREIGN KEY (`rubrica_id`) REFERENCES `rubricas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `fk_sesion_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sesion_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `temas`
--
ALTER TABLE `temas`
  ADD CONSTRAINT `fk_tema_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
