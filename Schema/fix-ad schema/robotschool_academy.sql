-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 31-03-2026 a las 20:40:44
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
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(10) UNSIGNED NOT NULL,
  `sede_id` int(10) UNSIGNED NOT NULL,
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

INSERT INTO `cursos` (`id`, `sede_id`, `nombre`, `imagen`, `introduccion`, `objetivos`, `edad_min`, `edad_max`, `valor`, `tipo_valor`, `cupo_maximo`, `publicado`, `orden`, `created_at`, `updated_at`) VALUES
(1, 1, 'LEGO BOOST Robótica', 'curso_1774993557_791.jpg', 'De Construir Castillos a Programar Sueños 🚀\r\n¿Recuerdas la primera vez que tu hijo unió dos ladrillos de LEGO y te mostró con orgullo su creación? Esa emoción es solo el principio. Hoy, te invitamos a dar el siguiente paso: el momento en que sus creaciones cobran vida.\r\nImagina a tu pequeño no solo siguiendo instrucciones, sino dándole \"voz\" a un robot, enseñándole a bailar a un gato mecánico o componiendo música con una guitarra que él mismo inventó. No se trata solo de piezas de plástico; se trata de darle las herramientas para que entienda el lenguaje del futuro. Al programar su primer movimiento, tu hijo no solo está jugando; está descubriendo que es capaz de resolver problemas, de pensar con lógica y, sobre todo, de que su imaginación no tiene límites.\r\nRegálales la confianza de saber que ellos pueden construir el mundo que imaginan.', 'Objetivos del Viaje Robótico 🎯\r\nPara que esta aventura sea tan educativa como divertida, nos enfocaremos en cuatro pilares:\r\nAlfabetización Digital Temprana: Aprender los fundamentos de la programación (lógica de bloques) de forma natural, antes incluso de escribir código complejo.\r\nResolución Creativa de Problemas: Fomentar el pensamiento crítico para superar retos técnicos: \"¿Por qué mi robot no gira a la derecha?\".\r\nDesarrollo de la Motricidad Fina y Espacial: Perfeccionar la precisión manual y la comprensión de estructuras físicas complejas mediante el montaje de engranajes y sensores.\r\nAutoconfianza y Resiliencia: Transformar el \"error\" en un paso necesario para el éxito, celebrando cada pequeño logro cuando el motor finalmente se activa.', 6, 8, 200000.00, 'mensual', 10, 1, 0, '2026-03-29 21:57:27', '2026-03-31 16:45:57'),
(2, 1, 'LEGO Spyke Prime', 'curso_1774993477_968.jpeg', '¿Quieres que tu hijo deje de ser solo un espectador de la tecnología y empiece a crearla?\r\nEn un mundo que cambia a pasos agigantados, saber programar y entender la robótica se ha convertido en el \"nuevo superpoder\". Con nuestro curso de LEGO SPIKE, transformamos la curiosidad natural de los niños en habilidades reales de ingeniería y computación.\r\nNo es solo armar robots; es aprender a pensar. Es ver cómo un montón de ladrillos cobra vida gracias al código que ellos mismos escribieron. Aquí, el error es parte del juego y cada reto superado construye una confianza inquebrantable.\r\n¡Dale a tu hijo las herramientas para diseñar el futuro, un ladrillo a la vez!', '🎯 Objetivos del Curso\r\nDominio de la Lógica de Programación: Aprender los fundamentos del código (bucles, sensores, condicionales) de forma visual y divertida.\r\nDesarrollo del Pensamiento Computacional: Fomentar la capacidad de descomponer problemas complejos en pasos pequeños y solucionables.\r\nHabilidades de Ingeniería Práctica: Entender el funcionamiento de engranajes, palancas y motores aplicados a robots reales.\r\nFomento de las Soft Skills: Potenciar el trabajo en equipo, la comunicación asertiva y la resiliencia ante la frustración.\r\nCreatividad Aplicada: Incentivar la creación de soluciones originales a problemas del mundo real.', 8, NULL, 210000.00, 'mensual', 20, 1, 0, '2026-03-31 12:07:35', '2026-03-31 16:44:37'),
(3, 1, 'LEGO WeDO', 'curso_1774993421_234.jpg', '🚀 Título del Curso: \"Pequeños Ingenieros: Crea, Programa y Despierta tu Genio con LEGO WeDo\"\r\nDescripción para Padres:\r\n¿Sabías que el juego favorito de tu hijo puede ser la puerta de entrada a las carreras del futuro? En este curso, los niños no solo arman figuras; ¡les dan vida! Usando la tecnología de LEGO WeDo, transformamos el tiempo de pantalla en un laboratorio de invención.\r\nImagina a tu hijo construyendo un satélite espacial o un robot de rescate y luego programándolo para que se mueva y reaccione al entorno. Aquí, el error es parte de la aventura y cada desafío resuelto construye confianza, lógica y pensamiento crítico. No solo estamos enseñando robótica; estamos entrenando a los próximos innovadores que diseñarán el mundo. ¡Dale a tu hijo el superpoder de crear tecnología, no solo de consumirla!', 'Para que los papás vean el valor académico, planteamos estos objetivos:\r\nFundamentos de Ingeniería: Comprender el uso de engranajes, palancas y poleas para crear movimiento físico.\r\nPensamiento Computacional: Aprender a descomponer problemas complejos en pasos lógicos mediante la programación por bloques.\r\nInvestigación Científica: Observar, predecir y probar hipótesis a través de modelos robóticos inspirados en la vida real.\r\nHabilidades Blandas (Soft Skills): Fomentar el trabajo en equipo, la comunicación de ideas y la resiliencia ante retos técnicos.', 5, 7, 200000.00, 'mensual', 10, 1, 0, '2026-03-31 14:13:09', '2026-03-31 16:43:41');

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
(52, 1, 'gal_1_1774993529_2.webp', NULL, 3, '2026-03-31 16:45:29');

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
(27, 1, 'LEGO BOOST Kit', 10, '', NULL);

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
(29, 1, 'Módulo 1: El Despertar de las Máquinas (Introducción)', 'Conocemos el \"cerebro\" (Hub) y cómo los sensores ven el mundo. Construcción de modelos básicos para entender el movimiento.', 1);

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
(3, 11, 1);

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
(3, 1, 'LEGO Wedo', 'LEGO® Education Para exploracion', 7, 1, '2026-03-31 14:16:32');

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
(7, 3, 1, 'LEGo Wedo', 'sabado', '08:00:00', '10:00:00', 'presencial', 1, 7, 7, 1, '2026-4', '2026-04-04', '2026-04-25', 1, '2026-03-31 14:15:01', '2026-03-31 14:17:17');

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
(10, 4, 2, 6);

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
(1, NULL, 'Administrador General', 'admin@robotschool.com.co', '$2y$10$JoByX6T8Y7khRUI5O/bxt.4LG6/pGBCFwEBQ.CTVQkZyU.jwIOGJK', 'admin_general', 1, '2026-03-31 20:07:56', '2026-03-29 21:32:35'),
(2, 1, 'Admin Sede 75', 'sede75@robotschool.com.co', '$2y$10$7zr3YteglcAG99mCVcgQtuy79Ui7PTzbsuJzhUbypU7dWVRScnORG', 'admin_sede', 1, NULL, '2026-03-29 21:32:35'),
(3, 2, 'Admin Sede Norte', 'sedenorte@robotschool.com.co', '$2y$10$7zr3YteglcAG99mCVcgQtuy79Ui7PTzbsuJzhUbypU7dWVRScnORG', 'admin_sede', 1, NULL, '2026-03-29 21:32:35'),
(4, 3, 'Admin Sede Cali', 'sedecali@robotschool.com.co', '$2y$10$7zr3YteglcAG99mCVcgQtuy79Ui7PTzbsuJzhUbypU7dWVRScnORG', 'admin_sede', 1, NULL, '2026-03-29 21:32:35'),
(5, NULL, 'Francisco Javier Puchana Hernández', 'fjpuchana@gmail.com', '$2y$10$zGykKdsZ2csd8QuXSxpIR.O3NgwXKovNKXmze1xscNhBrmZATm/8e', 'padre', 1, '2026-03-31 19:27:17', '2026-03-29 23:25:44'),
(10, NULL, 'Jose Alberto Molina Maturana', 'jamolina@robotschool.com.co', '$2y$10$5SczwOknUR8QaRwALiwvt.pa3GVczXuSP9ykmsE5rGd75Cnab4Vtq', 'coordinador_pedagogico', 1, NULL, '2026-03-31 16:38:10'),
(11, 1, 'Tomás Esteban Puchana Borda', 'tepuchana@robotschool.com.co', '$2y$10$3BKsjMEMF.GEOHDkRaEqQ./ODb5WCTY8hOfmzYCZpf8QSPLJIAJ56', 'docente', 1, '2026-03-31 16:50:13', '2026-03-31 16:38:54'),
(12, NULL, 'Claudia Liliana Borda Rodriguez', 'cborda18@hotmail.com', '$2y$10$3Ja9Hd6RskXpjHKiuYK52uO7lgQLBwYxY8YQL0GX6nkoe3pncJ/oC', 'padre', 1, '2026-03-31 20:07:30', '2026-03-31 20:06:01');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cursos_sede` (`sede_id`);

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
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `curso_galeria`
--
ALTER TABLE `curso_galeria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla `curso_materiales`
--
ALTER TABLE `curso_materiales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `curso_modulos`
--
ALTER TABLE `curso_modulos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `docente_grupos`
--
ALTER TABLE `docente_grupos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `grupo_equipos`
--
ALTER TABLE `grupo_equipos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `fk_cursos_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
