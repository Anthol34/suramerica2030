-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 02-10-2026 a las 09:46:25
-- Versión del servidor: 8.0.17
-- Versión de PHP: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `suramerica2030_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `abono`
--

CREATE TABLE `abono` (
  `ID_ABONO` int(11) NOT NULL,
  `ID_PASAJE` int(11) NOT NULL,
  `ID_FACTURA` int(11) NOT NULL,
  `MONTO` decimal(10,2) NOT NULL,
  `FECHA` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `abono`
--

INSERT INTO `abono` (`ID_ABONO`, `ID_PASAJE`, `ID_FACTURA`, `MONTO`, `FECHA`) VALUES
(1, 6, 20, '50.00', '2026-08-20 00:42:18'),
(2, 6, 22, '50.00', '2026-08-20 00:45:49'),
(3, 7, 24, '50.00', '2026-08-20 01:05:07'),
(4, 7, 25, '50.00', '2026-08-20 01:05:13'),
(5, 7, 28, '40.00', '2026-08-21 07:51:51'),
(6, 9, 31, '50.00', '2026-08-22 00:11:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bus`
--

CREATE TABLE `bus` (
  `ID_BUS` int(11) NOT NULL,
  `BUS_MARCA` varchar(20) NOT NULL,
  `BUS_MODELO` varchar(20) NOT NULL,
  `BUS_PLACA` varchar(10) NOT NULL,
  `BUS_ASIENTOS` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `bus`
--

INSERT INTO `bus` (`ID_BUS`, `BUS_MARCA`, `BUS_MODELO`, `BUS_PLACA`, `BUS_ASIENTOS`) VALUES
(2, 'Scania', 'K410', '000000', 44),
(3, 'CONEXION', 'CONEXION', 'CONEXION', 60);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cierre_caja`
--

CREATE TABLE `cierre_caja` (
  `ID_CIERRE` int(11) NOT NULL,
  `ID_USUARIO` int(11) NOT NULL,
  `ID_SUCURSAL` int(11) NOT NULL,
  `FECHA_CAJA` date NOT NULL,
  `TOTAL_INGRESOS` decimal(10,2) NOT NULL,
  `TOTAL_EGRESOS` decimal(10,2) NOT NULL,
  `CAPITAL_REPOSICION` decimal(10,2) DEFAULT '0.00',
  `COMISION_EMPLEADOS` decimal(10,2) DEFAULT '0.00',
  `VALOR_A_DEPOSITAR` decimal(10,2) DEFAULT '0.00',
  `SALDO_FINAL` decimal(10,2) NOT NULL,
  `UTILIDAD` decimal(10,2) DEFAULT '0.00',
  `FECHA_HORA` datetime DEFAULT CURRENT_TIMESTAMP,
  `CODIGO_CIERRE` varchar(50) DEFAULT NULL,
  `APERTURA_FECHA_HORA` datetime DEFAULT NULL,
  `VALOR_INICIAL` decimal(10,2) DEFAULT '0.00',
  `DEPOSITO` decimal(10,2) DEFAULT '0.00',
  `V_POR_DEPOSITAR` decimal(10,2) DEFAULT '0.00',
  `ESTADO` varchar(20) DEFAULT 'CERRADO',
  `DEPOSITO_NUM_DOCUMENTO` varchar(50) DEFAULT NULL,
  `DEPOSITO_ESTADO` varchar(20) DEFAULT 'INICIAL',
  `DEPOSITO_FECHA_APROBACION` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `cierre_caja`
--

INSERT INTO `cierre_caja` (`ID_CIERRE`, `ID_USUARIO`, `ID_SUCURSAL`, `FECHA_CAJA`, `TOTAL_INGRESOS`, `TOTAL_EGRESOS`, `CAPITAL_REPOSICION`, `COMISION_EMPLEADOS`, `VALOR_A_DEPOSITAR`, `SALDO_FINAL`, `UTILIDAD`, `FECHA_HORA`, `CODIGO_CIERRE`, `APERTURA_FECHA_HORA`, `VALOR_INICIAL`, `DEPOSITO`, `V_POR_DEPOSITAR`, `ESTADO`, `DEPOSITO_NUM_DOCUMENTO`, `DEPOSITO_ESTADO`, `DEPOSITO_FECHA_APROBACION`) VALUES
(2, 1, 1, '2026-08-18', '335.00', '100.00', '0.00', '23.50', '211.50', '235.00', '201.50', '2026-08-31 01:59:11', NULL, NULL, '0.00', '211.50', '0.00', 'CERRADO', 'Banco Pichincha: #PAPELETA-001 ($100.00) | Banco G', 'APROBADO', '2026-09-28 06:52:44'),
(3, 1, 1, '2026-09-27', '35.00', '0.00', '0.00', '3.50', '31.50', '35.00', '31.50', '2026-09-28 02:06:15', '270926000000150', NULL, '0.00', '31.50', '0.00', 'CERRADO', 'Banco Pichincha: #123315648989 ($10.00) | Banco Pi', 'APROBADO', '2026-09-28 07:47:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cierre_deposito_detalle`
--

CREATE TABLE `cierre_deposito_detalle` (
  `ID_DEPOSITO` int(11) NOT NULL,
  `ID_CIERRE` int(11) NOT NULL,
  `ID_USUARIO` int(11) NOT NULL,
  `BANCO` varchar(100) DEFAULT 'Banco Pichincha',
  `NUM_DOCUMENTO` varchar(100) NOT NULL,
  `MONTO` decimal(10,2) NOT NULL,
  `FECHA_DEPOSITO` datetime DEFAULT CURRENT_TIMESTAMP,
  `OBSERVACIONES` varchar(255) DEFAULT NULL,
  `ESTADO` varchar(20) DEFAULT 'REGISTRADO',
  `ID_USUARIO_AUDITA` int(11) DEFAULT NULL,
  `FECHA_AUDITORIA` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `cierre_deposito_detalle`
--

INSERT INTO `cierre_deposito_detalle` (`ID_DEPOSITO`, `ID_CIERRE`, `ID_USUARIO`, `BANCO`, `NUM_DOCUMENTO`, `MONTO`, `FECHA_DEPOSITO`, `OBSERVACIONES`, `ESTADO`, `ID_USUARIO_AUDITA`, `FECHA_AUDITORIA`) VALUES
(3, 2, 1, 'Banco Pichincha', 'PAPELETA-001', '100.00', '2026-09-28 01:57:24', 'Primer abono de caja', 'REGISTRADO', NULL, NULL),
(4, 2, 1, 'Banco Guayaquil', 'PAPELETA-002', '111.50', '2026-09-28 01:57:24', 'Segundo abono completando caja', 'REGISTRADO', NULL, NULL),
(5, 3, 1, 'Banco Pichincha', '123315648989', '10.00', '2026-09-28 02:10:17', '', 'APROBADO', NULL, '2026-09-28 07:47:52'),
(6, 3, 1, 'Banco Pichincha', '65545132031', '21.50', '2026-09-28 02:47:28', '', 'APROBADO', NULL, '2026-09-28 07:47:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudad`
--

CREATE TABLE `ciudad` (
  `ID_CIUDAD` int(11) NOT NULL,
  `NOMBRE` varchar(50) NOT NULL,
  `PAIS` varchar(30) NOT NULL,
  `ESTADO` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `ciudad`
--

INSERT INTO `ciudad` (`ID_CIUDAD`, `NOMBRE`, `PAIS`, `ESTADO`) VALUES
(1, 'Manta', 'ECUADOR', 1),
(2, 'Portoviejo', 'ECUADOR', 1),
(3, 'Guayaquil', 'ECUADOR', 1),
(4, 'Quito', 'ECUADOR', 1),
(5, 'Tulcán', 'ECUADOR', 1),
(6, 'Ipiales', 'COLOMBIA', 1),
(7, 'Pasto', 'COLOMBIA', 1),
(8, 'Cali', 'COLOMBIA', 1),
(9, 'Bogotá', 'COLOMBIA', 1),
(10, 'Bucaramanga', 'COLOMBIA', 1),
(11, 'Cúcuta', 'COLOMBIA', 1),
(12, 'San Cristóbal', 'VENEZUELA', 1),
(13, 'Barinas', 'VENEZUELA', 1),
(14, 'Valencia', 'VENEZUELA', 1),
(15, 'Maracay', 'VENEZUELA', 1),
(16, 'Caracas', 'VENEZUELA', 1),
(17, 'Barcelona', 'VENEZUELA', 1),
(18, 'Puerto La Cruz', 'VENEZUELA', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `ID_CLIENTE` varchar(15) NOT NULL,
  `CLI_NOMBRE` varchar(25) NOT NULL,
  `CLI_APELLIDO` varchar(25) NOT NULL,
  `CLI_TELEFONO` varchar(15) DEFAULT NULL,
  `CLI_CORREO` varchar(50) DEFAULT NULL,
  `CLI_NACIONALIDAD` varchar(50) DEFAULT 'ECUATORIANA',
  `CLI_FECHANAC` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`ID_CLIENTE`, `CLI_NOMBRE`, `CLI_APELLIDO`, `CLI_TELEFONO`, `CLI_CORREO`, `CLI_NACIONALIDAD`, `CLI_FECHANAC`) VALUES
('0987654321', 'Anthony Josue', 'Solórzano Roldán', '0967099841', 'josue10anthony1@gmail.com', 'ECUATORIANA', '2004-10-13'),
('11111111111', 'Josue', 'Roldan', '099999999999', 'jajacemamoxd123@gmail.com', 'ECUATORIANA', '2005-11-14'),
('1122334455', 'Melanie Michelle ', 'Solorzano Roldan', '0967099841', '', 'ECUATORIANA', '2026-09-01'),
('1234567890', 'Nataly Jean', 'Moreira Suarez', '0987654321', '', 'ECUATORIANA', '2026-09-01'),
('1301354966', 'Karen ', 'Moreira', '0963696202', 'josue10anthony1@gmail.com', 'VENEZOLANA', '2004-10-13'),
('1351304975', 'Anthony Josue', 'Solórzano Roldán', '0967099841', NULL, 'ECUATORIANA', NULL),
('2233445566', 'Prueba ', 'Test', '0987654321', '', 'ECUATORIANA', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `corredor`
--

CREATE TABLE `corredor` (
  `ID_CORREDOR` int(11) NOT NULL,
  `CODIGO` varchar(20) DEFAULT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `ORIGEN_CABECERA` varchar(50) NOT NULL,
  `DESTINO_TERMINAL` varchar(50) NOT NULL,
  `DIAS_SALIDA` varchar(50) DEFAULT '2,5',
  `HORA_SALIDA` time DEFAULT '10:00:00',
  `ESTADO` varchar(20) DEFAULT 'ACTIVO',
  `LIMITE_EQUIPAJE_KG` decimal(10,2) DEFAULT '35.00',
  `PRECIO_SOBREPESO_KG` decimal(10,2) DEFAULT '1.00',
  `PRECIO_SOBRE` decimal(10,2) DEFAULT '5.00',
  `PRECIO_VOLUMEN` decimal(10,2) DEFAULT '0.00',
  `COMISION_VALOR` decimal(5,2) DEFAULT '10.00',
  `TIPO_UTILIDAD` varchar(15) DEFAULT 'PORCENTAJE',
  `UTILIDAD_PASAJE` decimal(10,2) DEFAULT '100.00',
  `UTILIDAD_ENCOMIENDA` decimal(10,2) DEFAULT '100.00',
  `UTILIDAD_VALORES` decimal(10,2) DEFAULT '100.00',
  `FECHA_CREACION` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `corredor`
--

INSERT INTO `corredor` (`ID_CORREDOR`, `CODIGO`, `NOMBRE`, `ORIGEN_CABECERA`, `DESTINO_TERMINAL`, `DIAS_SALIDA`, `HORA_SALIDA`, `ESTADO`, `LIMITE_EQUIPAJE_KG`, `PRECIO_SOBREPESO_KG`, `PRECIO_SOBRE`, `PRECIO_VOLUMEN`, `COMISION_VALOR`, `TIPO_UTILIDAD`, `UTILIDAD_PASAJE`, `UTILIDAD_ENCOMIENDA`, `UTILIDAD_VALORES`, `FECHA_CREACION`) VALUES
(1, 'COR-01', 'Corredor Internacional Pacífico - Caracas', 'Manta', 'Caracas', '2,5', '10:00:00', 'ACTIVO', '35.00', '1.00', '20.00', '0.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', '2026-10-02 02:33:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `corredor_parada`
--

CREATE TABLE `corredor_parada` (
  `ID_CORREDOR_PARADA` int(11) NOT NULL,
  `ID_CORREDOR` int(11) NOT NULL,
  `ORDEN` int(11) NOT NULL,
  `CIUDAD` varchar(50) NOT NULL,
  `PAIS` varchar(30) NOT NULL,
  `TIPO_PARADA` varchar(30) DEFAULT 'ESCALA',
  `PERMITE_SUBIDA` tinyint(1) DEFAULT '1',
  `PERMITE_BAJADA` tinyint(1) DEFAULT '1',
  `TIEMPO_ESTIMADO_HORAS` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `corredor_parada`
--

INSERT INTO `corredor_parada` (`ID_CORREDOR_PARADA`, `ID_CORREDOR`, `ORDEN`, `CIUDAD`, `PAIS`, `TIPO_PARADA`, `PERMITE_SUBIDA`, `PERMITE_BAJADA`, `TIEMPO_ESTIMADO_HORAS`) VALUES
(1, 1, 1, 'Manta', 'ECUADOR', 'CABECERA', 1, 1, 0),
(2, 1, 2, 'Portoviejo', 'ECUADOR', 'ESCALA', 1, 1, 1),
(3, 1, 3, 'Guayaquil', 'ECUADOR', 'ESCALA', 1, 1, 4),
(4, 1, 4, 'Quito', 'ECUADOR', 'ESCALA', 1, 1, 10),
(5, 1, 5, 'Tulcán', 'ECUADOR', 'TRANSBORDO_FRONTERA', 1, 1, 16),
(6, 1, 6, 'Ipiales', 'COLOMBIA', 'TRANSBORDO_FRONTERA', 1, 1, 18),
(7, 1, 7, 'Cúcuta', 'COLOMBIA', 'TRANSBORDO_FRONTERA', 1, 1, 36),
(8, 1, 8, 'San Cristóbal', 'VENEZUELA', 'TRANSBORDO_FRONTERA', 1, 1, 38),
(9, 1, 9, 'Maracay', 'VENEZUELA', 'ESCALA', 1, 1, 50),
(10, 1, 10, 'Caracas', 'VENEZUELA', 'TERMINAL', 1, 1, 52);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `corredor_tramo_operativo`
--

CREATE TABLE `corredor_tramo_operativo` (
  `ID_CORREDOR_TRAMO` int(11) NOT NULL,
  `ID_CORREDOR` int(11) NOT NULL,
  `ORDEN_TRAMO` int(11) NOT NULL,
  `NOMBRE_TRAMO` varchar(100) NOT NULL,
  `ORIGEN_TRAMO` varchar(50) NOT NULL,
  `DESTINO_TRAMO` varchar(50) NOT NULL,
  `PAIS` varchar(30) NOT NULL,
  `ID_BUS_DEFAULT` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `corredor_tramo_operativo`
--

INSERT INTO `corredor_tramo_operativo` (`ID_CORREDOR_TRAMO`, `ID_CORREDOR`, `ORDEN_TRAMO`, `NOMBRE_TRAMO`, `ORIGEN_TRAMO`, `DESTINO_TRAMO`, `PAIS`, `ID_BUS_DEFAULT`) VALUES
(1, 1, 1, 'Tramo Nacional Ecuador', 'Manta', 'Tulcán', 'ECUADOR', NULL),
(2, 1, 2, 'Tramo Nacional Colombia', 'Ipiales', 'Cúcuta', 'COLOMBIA', NULL),
(3, 1, 3, 'Tramo Nacional Venezuela', 'San Cristóbal', 'Caracas', 'VENEZUELA', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encomienda`
--

CREATE TABLE `encomienda` (
  `ID_ENCOMIENDA` int(11) NOT NULL,
  `ID_FACTURA` int(11) NOT NULL,
  `ID_DESTINATARIO` varchar(15) DEFAULT NULL,
  `DESTINO_FINAL` varchar(100) DEFAULT NULL,
  `ENC_CANTIDAD` int(11) DEFAULT '1',
  `ENC_PESO` decimal(10,2) NOT NULL,
  `ENC_VOLUMEN` decimal(10,2) DEFAULT NULL,
  `ENC_TIPO` varchar(15) NOT NULL,
  `ENC_RETIRO` date DEFAULT NULL,
  `ENC_CONTENIDO` text DEFAULT NULL,
  `ENC_ESTADO` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `encomienda`
--

INSERT INTO `encomienda` (`ID_ENCOMIENDA`, `ID_FACTURA`, `ID_DESTINATARIO`, `DESTINO_FINAL`, `ENC_CANTIDAD`, `ENC_PESO`, `ENC_VOLUMEN`, `ENC_TIPO`, `ENC_RETIRO`, `ENC_CONTENIDO`, `ENC_ESTADO`) VALUES
(1, 4, NULL, NULL, 1, '5.00', NULL, 'Caja', NULL, 'Viveres', 'Anulado'),
(2, 7, NULL, NULL, 1, '5.00', NULL, 'Caja', '2026-09-03', 'Viveres', 'Entregado'),
(3, 8, NULL, NULL, 1, '5.00', NULL, 'Caja', '2026-09-03', 'Viveres', 'Entregado'),
(4, 11, NULL, NULL, 1, '5.50', NULL, 'Caja', '2026-08-19', 'Monitor', 'Entregado'),
(5, 26, '1234567890', NULL, 1, '10.00', NULL, 'Caja', '2026-08-22', 'Ropa', 'Entregado'),
(6, 27, '1234567890', NULL, 1, '5.00', '4.00', 'Maleta', '2026-09-03', 'ATUN', 'Entregado'),
(7, 36, '1122334455', NULL, 1, '5.00', '0.00', 'Caja', '2026-09-03', 'Ropa', 'Entregado'),
(8, 37, '1234567890', NULL, 1, '10.00', '0.00', 'Caja', '2026-09-03', 'Ropa', 'Entregado'),
(9, 44, '1234567890', NULL, 1, '10.00', '0.00', 'Maleta', '2026-09-03', 'Ropa', 'Entregado'),
(10, 49, '1234567890', NULL, 1, '11.00', '0.00', 'Bulto', NULL, 'Zapatos', 'Anulado'),
(11, 56, '1122334455', NULL, 3, '12.00', '0.00', 'MIXTO', '2026-09-03', '1 Bulto(s): Ropa | 1 Caja(s): Zapatos | 1 Maleta(s): Viveres', 'Entregado'),
(12, 57, '1122334455', NULL, 3, '15.00', '0.00', 'MIXTO', '2026-09-03', '1 Caja(s) [5Kg c/u]: Ropa | 1 Bulto(s) [5Kg c/u]: Zapatos | 1 Maleta(s) [5Kg c/u]: Viveres', 'Entregado'),
(13, 58, '1122334455', NULL, 3, '20.00', '0.00', 'Caja', '2026-09-03', '3 Caja(s): Latas', 'Entregado'),
(14, 59, '1234567890', NULL, 4, '15.00', '0.00', 'MIXTO', '2026-09-03', '1 Caja(s) [5Kg c/u]: Ropa | 1 Bulto(s) [5Kg c/u]: Zapatos | 1 Maleta(s) [5Kg c/u]: Latas | 1 Sobre(s', 'Entregado'),
(15, 69, '1234567890', 'BARCELONA', 4, '3.00', '0.00', 'MIXTO', '2026-09-03', '1 Caja(s) [1Kg c/u]: Ropa | 1 Bulto(s) [1Kg c/u]: Zapatos | 1 Maleta(s) [1Kg c/u]: Latas | 1 Sobre(s', 'Entregado'),
(16, 79, '1234567890', NULL, 1, '5.00', '0.00', 'Caja', NULL, '1 Caja(s) [5Kg c/u]: Ropa', 'Recibido');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `ID_FACTURA` int(11) NOT NULL,
  `ID_RUTA` int(11) NOT NULL,
  `ID_CLIENTE` varchar(15) NOT NULL,
  `ID_USUARIO` int(11) NOT NULL,
  `ID_SUCURSAL` int(11) NOT NULL,
  `FA_FECHA` date NOT NULL,
  `FA_HORA` time NOT NULL,
  `FECHA_VIAJE` date DEFAULT NULL,
  `FA_CANTIDAD` int(11) NOT NULL,
  `FA_METODO` varchar(15) DEFAULT NULL,
  `FA_SUBTOTAL` decimal(10,2) NOT NULL,
  `FA_TOTAL` decimal(10,2) NOT NULL,
  `FA_DETALLE` varchar(100) DEFAULT NULL,
  `FA_TIPO` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `factura`
--

INSERT INTO `factura` (`ID_FACTURA`, `ID_RUTA`, `ID_CLIENTE`, `ID_USUARIO`, `ID_SUCURSAL`, `FA_FECHA`, `FA_HORA`, `FECHA_VIAJE`, `FA_CANTIDAD`, `FA_METODO`, `FA_SUBTOTAL`, `FA_TOTAL`, `FA_DETALLE`, `FA_TIPO`) VALUES
(2, 1, '1351304975', 1, 1, '2026-08-18', '21:25:07', NULL, 1, 'EFECTIVO', '100.00', '110.00', 'Para: Eduardo Solórzano - CI: 1234567890', 'VALOR'),
(3, 1, '1351304975', 1, 1, '2026-08-18', '23:00:57', NULL, 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(4, 3, '1351304975', 1, 1, '2026-08-18', '23:57:01', NULL, 1, 'EFECTIVO', '35.00', '35.00', NULL, 'ENCOMIENDA'),
(5, 1, '1234567890', 1, 1, '2026-08-19', '00:46:47', '2026-08-25', 1, 'EFECTIVO', '180.00', '180.00', NULL, 'PASAJE'),
(6, 1, '1234567890', 1, 1, '2026-08-19', '00:46:59', '2026-08-25', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(7, 1, '1234567890', 1, 1, '2026-08-19', '00:47:24', '2026-08-25', 1, 'TRANSFERENCIA', '35.00', '35.00', NULL, 'ENCOMIENDA'),
(8, 1, '1234567890', 1, 1, '2026-08-19', '00:48:17', '2026-08-25', 1, 'TRANSFERENCIA', '35.00', '35.00', NULL, 'ENCOMIENDA'),
(10, 1, '1234567890', 1, 1, '2026-08-19', '00:59:19', '2026-08-25', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(11, 2, '1234567890', 1, 1, '2026-08-19', '01:04:11', '2026-08-25', 1, 'EFECTIVO', '38.50', '38.50', NULL, 'ENCOMIENDA'),
(14, 1, '0987654321', 1, 1, '2026-08-19', '18:18:31', '2026-08-25', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(16, 1, '0987654321', 1, 1, '2026-08-20', '00:09:14', '2026-08-25', 1, 'EFECTIVO', '10.00', '10.00', 'Cobro por exceso de equipaje', 'SOBREPESO'),
(18, 1, '0987654321', 1, 1, '2026-08-20', '00:26:45', '2026-08-28', 1, 'EFECTIVO', '50.00', '50.00', NULL, 'RESERVA INICIAL'),
(20, 1, '0987654321', 1, 1, '2026-08-20', '00:42:18', NULL, 1, 'EFECTIVO', '50.00', '50.00', NULL, 'ABONO PASAJE'),
(21, 1, '0987654321', 1, 1, '2026-08-20', '00:43:15', NULL, 1, 'EFECTIVO', '2.00', '2.00', 'Cobro por exceso de equipaje', 'SOBREPESO'),
(22, 1, '0987654321', 1, 1, '2026-08-20', '00:45:49', NULL, 1, 'EFECTIVO', '50.00', '50.00', NULL, 'ABONO PASAJE'),
(23, 1, '0987654321', 1, 1, '2026-08-20', '01:04:55', '2026-08-21', 1, 'EFECTIVO', '50.00', '50.00', NULL, 'RESERVA INICIAL'),
(24, 1, '0987654321', 1, 1, '2026-08-20', '01:05:07', NULL, 1, 'EFECTIVO', '50.00', '50.00', NULL, 'ABONO PASAJE'),
(25, 1, '0987654321', 1, 1, '2026-08-20', '01:05:13', NULL, 1, 'EFECTIVO', '50.00', '50.00', NULL, 'ABONO PASAJE'),
(26, 1, '1301354966', 1, 1, '2026-08-20', '02:24:12', NULL, 1, 'EFECTIVO', '70.00', '70.00', NULL, 'ENCOMIENDA'),
(27, 1, '0987654321', 1, 1, '2026-08-21', '06:39:53', '2026-08-30', 1, 'EFECTIVO', '40.00', '40.00', NULL, 'ENCOMIENDA'),
(28, 1, '0987654321', 1, 1, '2026-08-21', '07:51:51', '2026-08-21', 1, 'EFECTIVO', '40.00', '40.00', NULL, 'ABONO PASAJE'),
(29, 1, '0987654321', 1, 1, '2026-08-21', '08:21:42', '2026-08-28', 1, 'TRANSFERENCIA', '190.00', '190.00', NULL, 'PASAJE'),
(30, 1, '0987654321', 1, 1, '2026-08-21', '08:23:25', '2026-08-28', 1, 'EFECTIVO', '30.00', '30.00', NULL, 'RESERVA INICIAL'),
(31, 1, '0987654321', 1, 1, '2026-08-22', '00:11:49', '2026-08-28', 1, 'EFECTIVO', '50.00', '50.00', NULL, 'ABONO PASAJE'),
(32, 1, '0987654321', 1, 1, '2026-08-22', '00:15:20', '2026-08-25', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(33, 1, '0987654321', 1, 1, '2026-08-22', '00:19:11', '2026-08-25', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(35, 1, '1301354966', 1, 1, '2026-08-23', '01:58:01', '2026-08-25', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(36, 1, '0987654321', 1, 1, '2026-08-23', '02:40:12', '2026-08-30', 1, 'EFECTIVO', '35.00', '35.00', NULL, 'ENCOMIENDA'),
(37, 1, '0987654321', 1, 1, '2026-08-23', '02:54:16', '2026-08-30', 1, 'EFECTIVO', '70.00', '70.00', NULL, 'ENCOMIENDA'),
(38, 1, '0987654321', 1, 1, '2026-08-23', '03:05:53', '2026-08-23', 1, 'EFECTIVO', '200.00', '220.00', 'Para: Nataly Jean Moreira Suarez', 'VALOR'),
(39, 1, '1301354966', 1, 1, '2026-08-24', '03:48:28', '2026-08-25', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(40, 1, '1301354966', 1, 1, '2026-08-24', '03:51:18', '2026-08-24', 1, 'EFECTIVO', '300.00', '330.00', 'Para: Nataly Jean Moreira Suarez', 'VALOR'),
(41, 1, '0987654321', 2, 1, '2026-08-24', '13:58:53', '2026-08-25', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(42, 3, '11111111111', 1, 1, '2026-08-24', '16:12:51', '2026-08-28', 1, 'EFECTIVO', '60.00', '60.00', NULL, 'PASAJE'),
(43, 1, '11111111111', 1, 1, '2026-08-24', '16:28:49', '2026-08-25', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(44, 1, '11111111111', 1, 1, '2026-08-24', '16:31:23', '2026-08-29', 1, 'EFECTIVO', '70.00', '70.00', NULL, 'ENCOMIENDA'),
(45, 1, '1301354966', 1, 1, '2026-08-24', '16:59:00', '2026-08-24', 1, 'EFECTIVO', '300.00', '330.00', 'Para: Melanie Michelle  Solorzano Roldan', 'VALOR'),
(46, 1, '11111111111', 1, 1, '2026-08-24', '16:59:56', '2026-08-24', 1, 'TARJETA', '100.00', '110.00', 'Para: Melanie Michelle  Solorzano Roldan', 'VALOR'),
(47, 1, '1301354966', 1, 1, '2026-08-24', '17:00:43', '2026-08-24', 1, 'EFECTIVO', '200.00', '220.00', 'Para: Nataly Jean Moreira Suarez', 'VALOR'),
(48, 1, '11111111111', 1, 1, '2026-08-24', '17:02:55', '2026-08-25', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(49, 1, '0987654321', 1, 1, '2026-08-25', '03:28:46', NULL, 1, 'EFECTIVO', '77.00', '77.00', NULL, 'ENCOMIENDA'),
(50, 1, '0987654321', 1, 1, '2026-08-26', '08:20:15', '2026-08-28', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(51, 1, '0987654321', 1, 1, '2026-08-26', '08:21:01', '2026-08-28', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(52, 1, '2233445566', 2, 1, '2026-08-27', '03:38:58', '2026-08-28', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(53, 4, '11111111111', 2, 1, '2026-08-29', '02:41:45', '2026-09-01', 1, 'EFECTIVO', '130.00', '130.00', NULL, 'PASAJE'),
(54, 4, '0987654321', 2, 1, '2026-08-29', '02:44:38', '2026-09-01', 1, 'EFECTIVO', '130.00', '130.00', NULL, 'PASAJE'),
(55, 1, '1301354966', 2, 1, '2026-08-29', '02:45:21', '2026-09-01', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(56, 1, '0987654321', 1, 1, '2026-08-29', '19:56:18', '2026-08-30', 1, 'EFECTIVO', '84.00', '84.00', NULL, 'ENCOMIENDA'),
(57, 1, '11111111111', 1, 1, '2026-08-29', '20:14:43', '2026-08-29', 1, 'EFECTIVO', '105.00', '105.00', NULL, 'ENCOMIENDA'),
(58, 1, '11111111111', 1, 1, '2026-08-30', '00:13:33', '2026-08-30', 1, 'EFECTIVO', '140.00', '140.00', NULL, 'ENCOMIENDA'),
(59, 1, '0987654321', 1, 1, '2026-08-30', '01:05:36', '2026-09-01', 1, 'TARJETA', '125.00', '125.00', NULL, 'ENCOMIENDA'),
(60, 4, '0987654321', 1, 1, '2026-08-30', '02:34:17', '2026-09-01', 1, 'EFECTIVO', '10.00', '10.00', 'Cobro por exceso de equipaje', 'SOBREPESO'),
(61, 4, '11111111111', 1, 1, '2026-08-30', '22:44:00', '2026-09-01', 1, 'EFECTIVO', '10.00', '10.00', 'Cobro por exceso de equipaje', 'SOBREPESO'),
(63, 1, '0987654321', 1, 1, '2026-08-30', '22:59:49', '2026-09-01', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(64, 1, '0987654321', 1, 1, '2026-08-30', '23:01:15', '2026-09-01', 1, 'TRANSFERENCIA', '30.00', '30.00', 'Cobro por exceso de equipaje', 'SOBREPESO'),
(65, 1, '1301354966', 1, 1, '2026-08-31', '00:47:15', '2026-08-25', 1, 'EFECTIVO', '20.00', '20.00', 'Cobro por exceso de equipaje', 'SOBREPESO'),
(66, 1, '1234567890', 1, 1, '2026-08-31', '01:35:33', '2026-09-01', 4, 'EFECTIVO', '800.00', '800.00', NULL, 'PASAJE'),
(67, 5, '1234567890', 1, 1, '2026-09-01', '15:04:01', '2026-09-01', 2, 'EFECTIVO', '380.00', '380.00', NULL, 'PASAJE'),
(68, 5, '1234567890', 1, 1, '2026-09-01', '17:06:10', '2026-09-01', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(69, 1, '1301354966', 1, 1, '2026-09-01', '17:21:12', '2026-09-01', 1, 'EFECTIVO', '41.00', '41.00', NULL, 'ENCOMIENDA'),
(70, 5, '1234567890', 1, 1, '2026-09-02', '15:32:24', '2026-09-04', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(71, 5, '1122334455', 1, 1, '2026-09-03', '02:52:47', '2026-09-04', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(72, 5, '1234567890', 1, 1, '2026-09-04', '02:25:10', '2026-09-08', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(73, 5, '1234567890', 1, 1, '2026-09-04', '03:34:37', '2026-09-04', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(74, 5, '1122334455', 1, 1, '2026-09-04', '03:55:49', '2026-09-08', 1, 'EFECTIVO', '190.00', '190.00', NULL, 'PASAJE'),
(75, 5, '1122334455', 1, 1, '2026-09-04', '16:21:23', '2026-09-08', 1, 'EFECTIVO', '5.00', '5.00', 'Cobro por exceso de equipaje', 'SOBREPESO'),
(76, 1, '1234567890', 1, 1, '2026-09-25', '23:44:04', '2026-09-22', 2, 'EFECTIVO', '400.00', '400.00', NULL, 'PASAJE'),
(77, 5, '1234567890', 1, 1, '2026-09-25', '23:45:32', '2026-09-29', 2, 'EFECTIVO', '380.00', '380.00', NULL, 'PASAJE'),
(78, 1, '1234567890', 1, 1, '2026-09-26', '18:06:41', '2026-09-29', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(79, 1, '0987654321', 1, 1, '2026-09-27', '02:19:51', NULL, 1, 'EFECTIVO', '35.00', '35.00', NULL, 'ENCOMIENDA'),
(80, 1, '1234567890', 1, 1, '2026-10-02', '03:49:43', '2026-10-06', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE'),
(81, 1, '1234567890', 1, 1, '2026-10-02', '03:52:19', '2026-10-06', 1, 'EFECTIVO', '200.00', '200.00', NULL, 'PASAJE');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimiento_caja`
--

CREATE TABLE `movimiento_caja` (
  `ID_MOVIMIENTO` int(11) NOT NULL,
  `ID_USUARIO` int(11) NOT NULL,
  `ID_SUCURSAL` int(11) NOT NULL,
  `ID_FACTURA` int(11) DEFAULT NULL,
  `TIPO_MOVIMIENTO` enum('INGRESO','EGRESO') NOT NULL,
  `METODO_PAGO` enum('EFECTIVO','TRANSFERENCIA','TARJETA') NOT NULL,
  `MONTO` decimal(10,2) NOT NULL,
  `CONCEPTO` varchar(100) NOT NULL,
  `FECHA_HORA` datetime DEFAULT CURRENT_TIMESTAMP,
  `CODIGO_MOVIMIENTO` varchar(50) DEFAULT NULL,
  `ORIGEN_MOVIMIENTO` varchar(50) DEFAULT 'MOVIMIENTOS CAJA',
  `TIPO_EGRESO` varchar(100) DEFAULT NULL,
  `ESTADO_APROBACION` varchar(20) DEFAULT 'POR_APROBAR',
  `MOTIVO_RECHAZO` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `movimiento_caja`
--

INSERT INTO `movimiento_caja` (`ID_MOVIMIENTO`, `ID_USUARIO`, `ID_SUCURSAL`, `ID_FACTURA`, `TIPO_MOVIMIENTO`, `METODO_PAGO`, `MONTO`, `CONCEPTO`, `FECHA_HORA`, `CODIGO_MOVIMIENTO`, `ORIGEN_MOVIMIENTO`, `TIPO_EGRESO`, `ESTADO_APROBACION`, `MOTIVO_RECHAZO`) VALUES
(1, 1, 1, 2, 'INGRESO', 'EFECTIVO', '110.00', 'Recepción Envío de Valores', '2026-08-18 21:25:07', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(2, 1, 1, 3, 'INGRESO', 'EFECTIVO', '190.00', 'Venta de PASAJE', '2026-08-18 23:00:57', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(3, 1, 1, 2, 'EGRESO', 'EFECTIVO', '100.00', 'Entrega de Valores a Destinatario', '2026-08-18 23:05:41', NULL, 'MOVIMIENTOS CAJA', NULL, 'APROBADO', NULL),
(4, 1, 1, 4, 'INGRESO', 'EFECTIVO', '35.00', 'Venta de ENCOMIENDA', '2026-08-18 23:57:01', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(5, 1, 1, NULL, 'EGRESO', 'EFECTIVO', '200.00', 'Alquiler', '2026-08-19 00:44:52', NULL, 'MOVIMIENTOS CAJA', NULL, 'APROBADO', NULL),
(6, 1, 1, 5, 'INGRESO', 'EFECTIVO', '180.00', 'Venta de PASAJE', '2026-08-19 00:46:47', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(7, 1, 1, 6, 'INGRESO', 'EFECTIVO', '190.00', 'Venta de PASAJE', '2026-08-19 00:46:59', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(8, 1, 1, 7, 'INGRESO', 'TRANSFERENCIA', '35.00', 'Venta de ENCOMIENDA', '2026-08-19 00:47:24', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(9, 1, 1, 8, 'INGRESO', 'TRANSFERENCIA', '35.00', 'Venta de ENCOMIENDA', '2026-08-19 00:48:17', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(10, 1, 1, 10, 'INGRESO', 'EFECTIVO', '190.00', 'Venta de PASAJE', '2026-08-19 00:59:19', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(11, 1, 1, 11, 'INGRESO', 'EFECTIVO', '38.50', 'Venta de ENCOMIENDA', '2026-08-19 01:04:11', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(13, 1, 1, NULL, 'EGRESO', 'EFECTIVO', '20.00', 'JR', '2026-08-19 01:33:23', NULL, 'MOVIMIENTOS CAJA', NULL, 'RECHAZADO', 'No autorizado'),
(15, 1, 1, 14, 'INGRESO', 'EFECTIVO', '190.00', 'Venta/Reserva Pasaje', '2026-08-19 18:18:31', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(16, 1, 1, 16, 'INGRESO', 'EFECTIVO', '10.00', 'Cobro Sobrepeso Equipaje', '2026-08-20 00:09:14', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(17, 1, 1, 18, 'INGRESO', 'EFECTIVO', '50.00', 'Venta/Reserva Pasaje', '2026-08-20 00:26:45', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(18, 1, 1, 20, 'INGRESO', 'EFECTIVO', '50.00', 'Abono de Reserva', '2026-08-20 00:42:18', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(19, 1, 1, 21, 'INGRESO', 'EFECTIVO', '2.00', 'Cobro Sobrepeso Equipaje', '2026-08-20 00:43:15', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(20, 1, 1, 22, 'INGRESO', 'EFECTIVO', '50.00', 'Abono de Reserva', '2026-08-20 00:45:49', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(21, 1, 1, 23, 'INGRESO', 'EFECTIVO', '50.00', 'Venta/Reserva Pasaje', '2026-08-20 01:04:55', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(22, 1, 1, 24, 'INGRESO', 'EFECTIVO', '50.00', 'Abono de Reserva', '2026-08-20 01:05:07', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(23, 1, 1, 25, 'INGRESO', 'EFECTIVO', '50.00', 'Abono de Reserva', '2026-08-20 01:05:13', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(24, 1, 1, 26, 'INGRESO', 'EFECTIVO', '70.00', 'Recepción Encomienda', '2026-08-20 02:24:12', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(25, 1, 1, 27, 'INGRESO', 'EFECTIVO', '40.00', 'Recepción Encomienda', '2026-08-21 06:39:53', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(26, 1, 1, 18, 'EGRESO', 'EFECTIVO', '150.00', 'Devolución: Cambio de fecha', '2026-08-21 07:29:43', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(27, 1, 1, 28, 'INGRESO', 'EFECTIVO', '40.00', 'Abono de Reserva', '2026-08-21 07:51:51', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(28, 1, 1, 29, 'INGRESO', 'TRANSFERENCIA', '190.00', 'Venta/Reserva Pasaje', '2026-08-21 08:21:42', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(29, 1, 1, 30, 'INGRESO', 'EFECTIVO', '30.00', 'Venta/Reserva Pasaje', '2026-08-21 08:23:25', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(30, 1, 1, 31, 'INGRESO', 'EFECTIVO', '50.00', 'Abono de Reserva', '2026-08-22 00:11:49', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(31, 1, 1, NULL, 'EGRESO', 'EFECTIVO', '220.00', 'Alquiler', '2026-08-22 00:13:46', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(32, 1, 1, NULL, 'EGRESO', 'EFECTIVO', '20.00', 'JR', '2026-08-22 00:14:12', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(33, 1, 1, 32, 'INGRESO', 'EFECTIVO', '190.00', 'Venta/Reserva Pasaje', '2026-08-22 00:15:20', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(34, 1, 1, 33, 'INGRESO', 'EFECTIVO', '190.00', 'Venta de PASAJE', '2026-08-22 00:19:11', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(35, 1, 1, 35, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-08-23 01:58:01', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(36, 1, 1, 36, 'INGRESO', 'EFECTIVO', '35.00', 'Recepción Encomienda', '2026-08-23 02:40:12', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(37, 1, 1, 37, 'INGRESO', 'EFECTIVO', '70.00', 'Recepción Encomienda', '2026-08-23 02:54:16', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(38, 1, 1, 38, 'INGRESO', 'EFECTIVO', '220.00', 'Recepción Envío de Valores', '2026-08-23 03:05:53', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(39, 1, 1, 38, 'EGRESO', 'EFECTIVO', '200.00', 'Entrega de Valores a Destinatario', '2026-08-23 03:10:53', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(40, 1, 1, 39, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-08-24 03:48:28', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(41, 1, 1, 4, 'EGRESO', 'EFECTIVO', '35.00', 'Anulación de Encomienda (Reembolso)', '2026-08-24 03:49:08', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(42, 1, 1, 40, 'INGRESO', 'EFECTIVO', '330.00', 'Recepción Envío de Valores', '2026-08-24 03:51:18', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(43, 2, 1, 41, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-08-24 13:58:53', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(44, 1, 1, 42, 'INGRESO', 'EFECTIVO', '60.00', 'Venta de PASAJE', '2026-08-24 16:12:51', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(45, 1, 1, 43, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-08-24 16:28:49', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(46, 1, 1, 44, 'INGRESO', 'EFECTIVO', '70.00', 'Recepción Encomienda', '2026-08-24 16:31:23', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(47, 1, 1, 45, 'INGRESO', 'EFECTIVO', '330.00', 'Recepción Envío de Valores', '2026-08-24 16:59:00', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(48, 1, 1, 46, 'INGRESO', 'TARJETA', '110.00', 'Recepción Envío de Valores', '2026-08-24 16:59:56', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(49, 1, 1, 47, 'INGRESO', 'EFECTIVO', '220.00', 'Recepción Envío de Valores', '2026-08-24 17:00:43', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(50, 1, 1, 48, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-08-24 17:02:55', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(51, 1, 1, 49, 'INGRESO', 'EFECTIVO', '77.00', 'Recepción Encomienda', '2026-08-25 03:28:46', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(52, 1, 1, 50, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-08-26 08:20:15', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(53, 1, 1, 51, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-08-26 08:21:01', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(54, 2, 1, 52, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-08-27 03:38:58', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(55, 2, 1, 49, 'EGRESO', 'EFECTIVO', '77.00', 'Anulación de Encomienda (Reembolso)', '2026-08-29 00:15:46', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(56, 2, 1, 53, 'INGRESO', 'EFECTIVO', '130.00', 'Venta/Reserva Pasaje', '2026-08-29 02:41:45', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(57, 2, 1, 54, 'INGRESO', 'EFECTIVO', '130.00', 'Venta/Reserva Pasaje', '2026-08-29 02:44:38', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(58, 2, 1, 55, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-08-29 02:45:21', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(59, 2, 1, NULL, 'EGRESO', 'EFECTIVO', '50.00', 'Churquero', '2026-08-29 02:48:36', NULL, 'MOVIMIENTOS CAJA', NULL, 'RECHAZADO', 'no valido'),
(60, 1, 1, 56, 'INGRESO', 'EFECTIVO', '84.00', 'Recepción Encomienda', '2026-08-29 19:56:18', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(61, 1, 1, 57, 'INGRESO', 'EFECTIVO', '105.00', 'Recepción Encomienda', '2026-08-29 20:14:43', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(62, 1, 1, 58, 'INGRESO', 'EFECTIVO', '140.00', 'Recepción Encomienda', '2026-08-30 00:13:33', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(63, 1, 1, 59, 'INGRESO', 'TARJETA', '125.00', 'Recepción Encomienda', '2026-08-30 01:05:36', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(64, 1, 1, 60, 'INGRESO', 'EFECTIVO', '10.00', 'Cobro Sobrepeso Equipaje', '2026-08-30 02:34:17', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(65, 1, 1, 61, 'INGRESO', 'EFECTIVO', '10.00', 'Cobro Sobrepeso Equipaje', '2026-08-30 22:44:00', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(66, 1, 1, 63, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-08-30 22:59:49', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(67, 1, 1, 64, 'INGRESO', 'TRANSFERENCIA', '30.00', 'Cobro Sobrepeso Equipaje', '2026-08-30 23:01:15', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(68, 1, 1, 65, 'INGRESO', 'EFECTIVO', '20.00', 'Cobro Sobrepeso Equipaje', '2026-08-31 00:47:15', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(69, 1, 1, 66, 'INGRESO', 'EFECTIVO', '800.00', 'Venta/Reserva de 4 Pasajes', '2026-08-31 01:35:33', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(70, 1, 1, 67, 'INGRESO', 'EFECTIVO', '380.00', 'Venta/Reserva de 2 Pasajes', '2026-09-01 15:04:01', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(71, 1, 1, 68, 'INGRESO', 'EFECTIVO', '190.00', 'Venta/Reserva Pasaje', '2026-09-01 17:06:10', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(72, 1, 1, 69, 'INGRESO', 'EFECTIVO', '41.00', 'Recepción Encomienda', '2026-09-01 17:21:12', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(73, 1, 1, 70, 'INGRESO', 'EFECTIVO', '190.00', 'Venta/Reserva Pasaje', '2026-09-02 15:32:24', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(74, 1, 1, 71, 'INGRESO', 'EFECTIVO', '190.00', 'Venta/Reserva Pasaje', '2026-09-03 02:52:47', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(75, 1, 1, 72, 'INGRESO', 'EFECTIVO', '190.00', 'Venta/Reserva Pasaje', '2026-09-04 02:25:10', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(76, 1, 1, 73, 'INGRESO', 'EFECTIVO', '190.00', 'Venta/Reserva Pasaje', '2026-09-04 03:34:37', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(77, 1, 1, 74, 'INGRESO', 'EFECTIVO', '190.00', 'Venta/Reserva Pasaje', '2026-09-04 03:55:49', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(78, 1, 1, 75, 'INGRESO', 'EFECTIVO', '5.00', 'Cobro Sobrepeso Equipaje', '2026-09-04 16:21:23', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(79, 1, 1, 76, 'INGRESO', 'EFECTIVO', '400.00', 'Venta/Reserva de 2 Pasajes', '2026-09-25 23:44:04', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(80, 1, 1, 77, 'INGRESO', 'EFECTIVO', '380.00', 'Venta/Reserva de 2 Pasajes', '2026-09-25 23:45:32', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(81, 1, 1, 78, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-09-26 18:06:41', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(82, 1, 1, 79, 'INGRESO', 'EFECTIVO', '35.00', 'Recepción Encomienda', '2026-09-27 02:19:51', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(86, 1, 1, 47, 'EGRESO', 'EFECTIVO', '200.00', 'Entrega de Valores a Destinatario', '2026-09-28 03:33:28', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(87, 1, 1, 80, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-10-02 03:49:43', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL),
(88, 1, 1, 81, 'INGRESO', 'EFECTIVO', '200.00', 'Venta/Reserva Pasaje', '2026-10-02 03:52:19', NULL, 'MOVIMIENTOS CAJA', NULL, 'POR_APROBAR', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pasaje`
--

CREATE TABLE `pasaje` (
  `ID_PASAJE` int(11) NOT NULL,
  `ID_FACTURA` int(11) NOT NULL,
  `ID_CLIENTE` varchar(20) DEFAULT NULL,
  `ID_BUS` int(11) DEFAULT NULL,
  `PAX_ASIENTO` int(11) DEFAULT NULL,
  `ESTADO` varchar(20) DEFAULT 'PAGADO',
  `PRECIO_TOTAL` decimal(10,2) DEFAULT '0.00',
  `SALDO` decimal(10,2) DEFAULT '0.00',
  `PESO_MANO` decimal(10,2) DEFAULT '0.00',
  `PESO_BODEGA` decimal(10,2) DEFAULT '0.00',
  `OBSERVACION` varchar(255) DEFAULT NULL,
  `PAX_MENOR` varchar(15) DEFAULT NULL,
  `PAX_PET` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `pasaje`
--

INSERT INTO `pasaje` (`ID_PASAJE`, `ID_FACTURA`, `ID_CLIENTE`, `ID_BUS`, `PAX_ASIENTO`, `ESTADO`, `PRECIO_TOTAL`, `SALDO`, `PESO_MANO`, `PESO_BODEGA`, `OBSERVACION`, `PAX_MENOR`, `PAX_PET`) VALUES
(1, 3, NULL, 2, NULL, 'DEVUELTO', '0.00', '0.00', '0.00', '0.00', 'Devolución: Cambio de fecha', NULL, NULL),
(2, 5, NULL, 2, 40, 'PAGADO', '0.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(3, 6, NULL, 2, 40, 'PAGADO', '0.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(5, 14, '0987654321', 2, 3, 'PAGADO', '190.00', '0.00', '10.00', '30.00', NULL, NULL, NULL),
(6, 18, '0987654321', 2, NULL, 'DEVUELTO', '190.00', '40.00', '10.00', '26.00', 'Devolución: Cambio de fecha', NULL, NULL),
(7, 23, '0987654321', 2, 25, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(8, 29, '0987654321', 2, 4, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(10, 32, '0987654321', 2, 20, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(11, 33, NULL, 2, 6, 'PAGADO', '0.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(12, 35, '1301354966', 2, 22, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(13, 39, '1301354966', 2, 2, 'PAGADO', '200.00', '0.00', '10.00', '35.00', NULL, NULL, NULL),
(14, 41, '0987654321', 2, 4, 'PAGADO', '200.00', '0.00', '10.00', '25.00', NULL, NULL, NULL),
(15, 42, NULL, 2, NULL, 'DEVUELTO', '0.00', '0.00', '0.00', '0.00', 'Devolución: Cambio de fecha', NULL, NULL),
(16, 43, '11111111111', 2, 1, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(17, 48, '11111111111', 2, 5, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(18, 50, '0987654321', 2, 1, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(19, 51, '0987654321', 2, 2, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(20, 52, '2233445566', 2, 3, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(21, 53, '11111111111', 2, 1, 'PAGADO', '130.00', '0.00', '10.00', '35.00', NULL, NULL, NULL),
(22, 54, '0987654321', 2, 2, 'PAGADO', '130.00', '0.00', '10.00', '35.00', NULL, NULL, NULL),
(23, 55, '1301354966', 2, 1, 'PAGADO', '200.00', '0.00', '10.00', '25.00', NULL, NULL, NULL),
(24, 63, '0987654321', 2, 3, 'PAGADO', '200.00', '0.00', '10.00', '40.00', NULL, NULL, NULL),
(25, 66, '1234567890', 2, 2, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(26, 66, '1122334455', 2, 4, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(27, 66, '0987654321', 2, 5, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(28, 66, '2233445566', 2, 6, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(29, 67, '1234567890', 2, 1, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(30, 67, '1122334455', 2, 2, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(31, 68, '1234567890', 3, NULL, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(32, 70, '1234567890', 3, 1, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(33, 71, '1122334455', 3, NULL, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(34, 72, '1234567890', 3, 1, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(35, 73, '1234567890', 3, NULL, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(36, 74, '1122334455', 3, 2, 'PAGADO', '190.00', '0.00', '10.00', '30.00', NULL, NULL, NULL),
(37, 76, '1234567890', 2, NULL, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(38, 76, '1234567890', 2, NULL, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(39, 77, '1234567890', 2, 1, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(40, 77, '1122334455', 2, 2, 'PAGADO', '190.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(41, 78, '1234567890', 2, NULL, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(42, 80, '1234567890', 3, 1, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL),
(43, 81, '1234567890', 3, 2, 'PAGADO', '200.00', '0.00', '0.00', '0.00', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pasaje_tramo`
--

CREATE TABLE `pasaje_tramo` (
  `ID_PASAJE_TRAMO` int(11) NOT NULL,
  `ID_PASAJE` int(11) NOT NULL,
  `ID_RUTA_TRAMO` int(11) NOT NULL,
  `FECHA_TRAMO` date DEFAULT NULL,
  `ID_BUS` int(11) DEFAULT NULL,
  `PAX_ASIENTO` int(11) DEFAULT NULL,
  `ESTADO_TRAMO` varchar(50) DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva`
--

CREATE TABLE `reserva` (
  `ID_RESERVA` int(11) NOT NULL,
  `ID_FACTURA` int(11) NOT NULL,
  `RE_FECHA` date NOT NULL,
  `RE_ABONO` decimal(10,2) DEFAULT NULL,
  `RE_RESTANTE` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `ID_ROL` int(11) NOT NULL,
  `NOMBRE_ROL` varchar(20) NOT NULL,
  `P_VENTA` tinyint(1) DEFAULT '0',
  `P_ANULAR` tinyint(1) DEFAULT '0',
  `P_CAJA` tinyint(1) DEFAULT '0',
  `P_FLOTA` tinyint(1) DEFAULT '0',
  `P_USUARIOS` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`ID_ROL`, `NOMBRE_ROL`, `P_VENTA`, `P_ANULAR`, `P_CAJA`, `P_FLOTA`, `P_USUARIOS`) VALUES
(1, 'Admin', 1, 1, 1, 1, 1),
(2, 'Colaborador', 1, 0, 1, 0, 0),
(3, 'Sustituto', 1, 0, 0, 0, 0),
(4, 'Colaborador Especial', 1, 1, 1, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ruta`
--

CREATE TABLE `ruta` (
  `ID_RUTA` int(11) NOT NULL,
  `ID_CORREDOR` int(11) DEFAULT NULL,
  `RUT_ORIGEN` varchar(50) NOT NULL,
  `RUT_DESTINO` varchar(50) NOT NULL,
  `TIPO_VIAJE` varchar(20) DEFAULT 'DIRECTO',
  `DIAS_SALIDA` varchar(50) DEFAULT NULL,
  `RUT_SAHORA` time DEFAULT NULL,
  `PRECIO_PASAJE` decimal(10,2) DEFAULT '0.00',
  `PRECIO_ENCOMIENDA_KG` decimal(10,2) DEFAULT '0.00',
  `PRECIO_SOBRE` decimal(10,2) DEFAULT '5.00',
  `PRECIO_VOLUMEN` decimal(10,2) DEFAULT '0.00',
  `LIMITE_EQUIPAJE_KG` decimal(10,2) DEFAULT '35.00',
  `PRECIO_SOBREPESO_KG` decimal(10,2) DEFAULT '1.00',
  `COMISION_VALOR` decimal(5,2) DEFAULT '10.00',
  `TIPO_UTILIDAD` varchar(15) DEFAULT 'PORCENTAJE',
  `UTILIDAD_PASAJE` decimal(10,2) DEFAULT '100.00',
  `UTILIDAD_ENCOMIENDA` decimal(10,2) DEFAULT '100.00',
  `UTILIDAD_VALORES` decimal(10,2) DEFAULT '100.00',
  `ESTADO` varchar(20) DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `ruta`
--

INSERT INTO `ruta` (`ID_RUTA`, `ID_CORREDOR`, `RUT_ORIGEN`, `RUT_DESTINO`, `TIPO_VIAJE`, `DIAS_SALIDA`, `RUT_SAHORA`, `PRECIO_PASAJE`, `PRECIO_ENCOMIENDA_KG`, `PRECIO_SOBRE`, `PRECIO_VOLUMEN`, `LIMITE_EQUIPAJE_KG`, `PRECIO_SOBREPESO_KG`, `COMISION_VALOR`, `TIPO_UTILIDAD`, `UTILIDAD_PASAJE`, `UTILIDAD_ENCOMIENDA`, `UTILIDAD_VALORES`, `ESTADO`) VALUES
(1, 1, 'Manta', 'Caracas', 'DIRECTO', '2,5', '10:00:00', '200.00', '7.00', '20.00', '10.00', '35.00', '2.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(2, 1, 'Quito', 'Caracas', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(3, 1, 'Cucuta', 'Caracas', 'DIRECTO', '5', '17:00:00', '60.00', '0.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(4, 1, 'Manta', 'Cucuta', 'DIRECTO', '2,5', '10:00:00', '130.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '20.00', '100.00', '100.00', 'ACTIVO'),
(5, 1, 'Manta', 'Caracas', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(6, 1, 'Manta', 'Portoviejo', 'DIRECTO', '2,5', '10:00:00', '12.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(7, 1, 'Manta', 'Guayaquil', 'DIRECTO', '2,5', '10:00:00', '24.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(8, 1, 'Manta', 'Quito', 'DIRECTO', '2,5', '10:00:00', '36.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(9, 1, 'Manta', 'Tulcán', 'DIRECTO', '2,5', '10:00:00', '48.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(10, 1, 'Manta', 'Ipiales', 'CONEXION', '2,5', '10:00:00', '120.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(11, 1, 'Manta', 'Cúcuta', 'CONEXION', '2,5', '10:00:00', '120.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(12, 1, 'Manta', 'San Cristóbal', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(13, 1, 'Manta', 'Maracay', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(14, 1, 'Portoviejo', 'Guayaquil', 'DIRECTO', '2,5', '10:00:00', '12.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(15, 1, 'Portoviejo', 'Quito', 'DIRECTO', '2,5', '10:00:00', '24.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(16, 1, 'Portoviejo', 'Tulcán', 'DIRECTO', '2,5', '10:00:00', '36.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(17, 1, 'Portoviejo', 'Ipiales', 'CONEXION', '2,5', '10:00:00', '120.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(18, 1, 'Portoviejo', 'Cúcuta', 'CONEXION', '2,5', '10:00:00', '120.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(19, 1, 'Portoviejo', 'San Cristóbal', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(20, 1, 'Portoviejo', 'Maracay', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(21, 1, 'Portoviejo', 'Caracas', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(22, 1, 'Guayaquil', 'Quito', 'DIRECTO', '2,5', '10:00:00', '12.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(23, 1, 'Guayaquil', 'Tulcán', 'DIRECTO', '2,5', '10:00:00', '24.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(24, 1, 'Guayaquil', 'Ipiales', 'CONEXION', '2,5', '10:00:00', '120.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(25, 1, 'Guayaquil', 'Cúcuta', 'CONEXION', '2,5', '10:00:00', '120.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(26, 1, 'Guayaquil', 'San Cristóbal', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(27, 1, 'Guayaquil', 'Maracay', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(28, 1, 'Guayaquil', 'Caracas', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(29, 1, 'Quito', 'Tulcán', 'DIRECTO', '2,5', '10:00:00', '12.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(30, 1, 'Quito', 'Ipiales', 'CONEXION', '2,5', '10:00:00', '120.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(31, 1, 'Quito', 'Cúcuta', 'CONEXION', '2,5', '10:00:00', '120.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(32, 1, 'Quito', 'San Cristóbal', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(33, 1, 'Quito', 'Maracay', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(34, 1, 'Tulcán', 'Ipiales', 'CONEXION', '2,5', '10:00:00', '120.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(35, 1, 'Tulcán', 'Cúcuta', 'CONEXION', '2,5', '10:00:00', '120.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(36, 1, 'Tulcán', 'San Cristóbal', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(37, 1, 'Tulcán', 'Maracay', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(38, 1, 'Tulcán', 'Caracas', 'CONEXION', '2,5', '10:00:00', '190.00', '7.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(39, 1, 'Ipiales', 'Cúcuta', 'DIRECTO', '2,5', '10:00:00', '12.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(40, 1, 'Ipiales', 'San Cristóbal', 'CONEXION', '2,5', '10:00:00', '60.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(41, 1, 'Ipiales', 'Maracay', 'CONEXION', '2,5', '10:00:00', '60.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(42, 1, 'Ipiales', 'Caracas', 'CONEXION', '2,5', '10:00:00', '60.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(43, 1, 'Cúcuta', 'San Cristóbal', 'CONEXION', '2,5', '10:00:00', '60.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(44, 1, 'Cúcuta', 'Maracay', 'CONEXION', '2,5', '10:00:00', '60.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(45, 1, 'Cúcuta', 'Caracas', 'CONEXION', '2,5', '10:00:00', '60.00', '5.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(46, 1, 'San Cristóbal', 'Maracay', 'DIRECTO', '2,5', '10:00:00', '12.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(47, 1, 'San Cristóbal', 'Caracas', 'DIRECTO', '2,5', '10:00:00', '24.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO'),
(48, 1, 'Maracay', 'Caracas', 'DIRECTO', '2,5', '10:00:00', '12.00', '2.00', '5.00', '0.00', '35.00', '1.00', '10.00', 'PORCENTAJE', '100.00', '100.00', '100.00', 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ruta_tramo`
--

CREATE TABLE `ruta_tramo` (
  `ID_RUTA_TRAMO` int(11) NOT NULL,
  `ID_RUTA` int(11) NOT NULL,
  `ORDEN` int(11) NOT NULL,
  `ORIGEN_TRAMO` varchar(100) DEFAULT NULL,
  `DESTINO_TRAMO` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `ruta_tramo`
--

INSERT INTO `ruta_tramo` (`ID_RUTA_TRAMO`, `ID_RUTA`, `ORDEN`, `ORIGEN_TRAMO`, `DESTINO_TRAMO`) VALUES
(4, 10, 1, 'Manta', 'Tulcán'),
(5, 10, 2, 'Ipiales', 'Ipiales'),
(6, 11, 1, 'Manta', 'Tulcán'),
(7, 11, 2, 'Ipiales', 'Cúcuta'),
(8, 12, 1, 'Manta', 'Tulcán'),
(9, 12, 2, 'Ipiales', 'Cúcuta'),
(10, 12, 3, 'San Cristóbal', 'San Cristóbal'),
(11, 13, 1, 'Manta', 'Tulcán'),
(12, 13, 2, 'Ipiales', 'Cúcuta'),
(13, 13, 3, 'San Cristóbal', 'Maracay'),
(14, 5, 1, 'Manta', 'Tulcán'),
(15, 5, 2, 'Ipiales', 'Cúcuta'),
(16, 5, 3, 'San Cristóbal', 'Caracas'),
(17, 17, 1, 'Portoviejo', 'Tulcán'),
(18, 17, 2, 'Ipiales', 'Ipiales'),
(19, 18, 1, 'Portoviejo', 'Tulcán'),
(20, 18, 2, 'Ipiales', 'Cúcuta'),
(21, 19, 1, 'Portoviejo', 'Tulcán'),
(22, 19, 2, 'Ipiales', 'Cúcuta'),
(23, 19, 3, 'San Cristóbal', 'San Cristóbal'),
(24, 20, 1, 'Portoviejo', 'Tulcán'),
(25, 20, 2, 'Ipiales', 'Cúcuta'),
(26, 20, 3, 'San Cristóbal', 'Maracay'),
(27, 21, 1, 'Portoviejo', 'Tulcán'),
(28, 21, 2, 'Ipiales', 'Cúcuta'),
(29, 21, 3, 'San Cristóbal', 'Caracas'),
(30, 24, 1, 'Guayaquil', 'Tulcán'),
(31, 24, 2, 'Ipiales', 'Ipiales'),
(32, 25, 1, 'Guayaquil', 'Tulcán'),
(33, 25, 2, 'Ipiales', 'Cúcuta'),
(34, 26, 1, 'Guayaquil', 'Tulcán'),
(35, 26, 2, 'Ipiales', 'Cúcuta'),
(36, 26, 3, 'San Cristóbal', 'San Cristóbal'),
(37, 27, 1, 'Guayaquil', 'Tulcán'),
(38, 27, 2, 'Ipiales', 'Cúcuta'),
(39, 27, 3, 'San Cristóbal', 'Maracay'),
(40, 28, 1, 'Guayaquil', 'Tulcán'),
(41, 28, 2, 'Ipiales', 'Cúcuta'),
(42, 28, 3, 'San Cristóbal', 'Caracas'),
(43, 30, 1, 'Quito', 'Tulcán'),
(44, 30, 2, 'Ipiales', 'Ipiales'),
(45, 31, 1, 'Quito', 'Tulcán'),
(46, 31, 2, 'Ipiales', 'Cúcuta'),
(47, 32, 1, 'Quito', 'Tulcán'),
(48, 32, 2, 'Ipiales', 'Cúcuta'),
(49, 32, 3, 'San Cristóbal', 'San Cristóbal'),
(50, 33, 1, 'Quito', 'Tulcán'),
(51, 33, 2, 'Ipiales', 'Cúcuta'),
(52, 33, 3, 'San Cristóbal', 'Maracay'),
(53, 2, 1, 'Quito', 'Tulcán'),
(54, 2, 2, 'Ipiales', 'Cúcuta'),
(55, 2, 3, 'San Cristóbal', 'Caracas'),
(56, 34, 1, 'Tulcán', 'Tulcán'),
(57, 34, 2, 'Ipiales', 'Ipiales'),
(58, 35, 1, 'Tulcán', 'Tulcán'),
(59, 35, 2, 'Ipiales', 'Cúcuta'),
(60, 36, 1, 'Tulcán', 'Tulcán'),
(61, 36, 2, 'Ipiales', 'Cúcuta'),
(62, 36, 3, 'San Cristóbal', 'San Cristóbal'),
(63, 37, 1, 'Tulcán', 'Tulcán'),
(64, 37, 2, 'Ipiales', 'Cúcuta'),
(65, 37, 3, 'San Cristóbal', 'Maracay'),
(66, 38, 1, 'Tulcán', 'Tulcán'),
(67, 38, 2, 'Ipiales', 'Cúcuta'),
(68, 38, 3, 'San Cristóbal', 'Caracas'),
(69, 40, 1, 'Ipiales', 'Cúcuta'),
(70, 40, 2, 'San Cristóbal', 'San Cristóbal'),
(71, 41, 1, 'Ipiales', 'Cúcuta'),
(72, 41, 2, 'San Cristóbal', 'Maracay'),
(73, 42, 1, 'Ipiales', 'Cúcuta'),
(74, 42, 2, 'San Cristóbal', 'Caracas'),
(75, 43, 1, 'Cúcuta', 'Cúcuta'),
(76, 43, 2, 'San Cristóbal', 'San Cristóbal'),
(77, 44, 1, 'Cúcuta', 'Cúcuta'),
(78, 44, 2, 'San Cristóbal', 'Maracay'),
(79, 45, 1, 'Cúcuta', 'Cúcuta'),
(80, 45, 2, 'San Cristóbal', 'Caracas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sobrepeso`
--

CREATE TABLE `sobrepeso` (
  `ID_SOBREPESO` int(11) NOT NULL,
  `ID_FACTURA` int(11) NOT NULL,
  `SP_TIPO` varchar(25) NOT NULL,
  `SP_PESO` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `socio`
--

CREATE TABLE `socio` (
  `ID_SOCIO` int(11) NOT NULL,
  `ID_BUS` int(11) NOT NULL,
  `SOC_NOMBRE` varchar(25) NOT NULL,
  `SOC_APELLIDOS` varchar(25) NOT NULL,
  `SOC_TELEFONO` varchar(15) NOT NULL,
  `SOC_BANK` varchar(20) DEFAULT NULL,
  `SOC_CUENTA` varchar(20) DEFAULT NULL,
  `SOC_CORREO` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `socio`
--

INSERT INTO `socio` (`ID_SOCIO`, `ID_BUS`, `SOC_NOMBRE`, `SOC_APELLIDOS`, `SOC_TELEFONO`, `SOC_BANK`, `SOC_CUENTA`, `SOC_CORREO`) VALUES
(1, 2, 'Rodrigo', 'Zambrano', '0987654321', 'Pichincha', '220256786', NULL),
(2, 3, 'Conexion', 'Conexion', '0987654321', 'Pichincha', '22022022022', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursal`
--

CREATE TABLE `sucursal` (
  `ID_SUCURSAL` int(11) NOT NULL,
  `PAIS` varchar(20) NOT NULL,
  `CIUDAD` varchar(50) NOT NULL,
  `DIRECCION` varchar(150) DEFAULT NULL,
  `TELEFONO` varchar(50) DEFAULT NULL,
  `COMISION_EMPLEADO_PCT` decimal(5,2) DEFAULT '0.00',
  `GASTO_FIJO_DIARIO` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `sucursal`
--

INSERT INTO `sucursal` (`ID_SUCURSAL`, `PAIS`, `CIUDAD`, `DIRECCION`, `TELEFONO`, `COMISION_EMPLEADO_PCT`, `GASTO_FIJO_DIARIO`) VALUES
(1, 'ECUADOR', 'MANTA', 'Calle 17  Av. 24', '0967049757', '10.00', '10.00'),
(2, 'VENEZUELA', 'CARACAS', 'Terminal Privado San Martín', NULL, '0.00', '0.00'),
(3, 'ECUADOR', 'GUAYAQUIL', 'Editar', NULL, '15.00', '0.00'),
(4, 'ECUADOR', 'PORTOVIEJO', 'Editar', NULL, '0.00', '0.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `ID_USUARIO` int(11) NOT NULL,
  `ID_ROL` int(11) NOT NULL,
  `ID_SUCURSAL` int(11) NOT NULL,
  `NOMBRE` varchar(50) NOT NULL,
  `USUARIO` varchar(50) NOT NULL,
  `EMAIL` varchar(50) NOT NULL,
  `PASSWORD_HASH` varchar(255) NOT NULL,
  `ESTADO` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`ID_USUARIO`, `ID_ROL`, `ID_SUCURSAL`, `NOMBRE`, `USUARIO`, `EMAIL`, `PASSWORD_HASH`, `ESTADO`) VALUES
(1, 1, 1, 'Anthony', 'admin', 'admin@suramerica2030.com', '$2y$10$hTbx43X4soSsH2gcSyhTreSowVZeNAvqfQSKzSLUzqYsfGu0lcr1q', 1),
(2, 4, 1, 'Anthony Solórzano', 'anthony.solorzano', 'josue10anthony1@gmail.com', '$2y$10$wsXa5.BsTPoGYsZHGDf/UOPLjrgnQOZG9hdUtUwyrg/iBDXWeLi4K', 1),
(3, 2, 1, 'Eduardo Solorzano', 'eduardo.solorzano', 'hola@gmail.com', '$2y$10$P5.q8XjkzFiDzBAF68yYReKJpso/MwM9ZfsUJ3EITpziEtZ0QBfQu', 1),
(4, 2, 2, 'Jamileth Regalado', 'jamileth.regalado', 'jamilethregalado@gmail.com', '$2y$10$2oMGmrNcaZPK/3SFURu0R.Ve15jO.jaS3rSbLKbOibyB0KboBTome', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valor`
--

CREATE TABLE `valor` (
  `ID_VALOR` int(11) NOT NULL,
  `ID_FACTURA` int(11) NOT NULL,
  `ID_DESTINATARIO` varchar(15) DEFAULT NULL,
  `VA_RETIRO` date DEFAULT NULL,
  `VA_ESTADO` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `valor`
--

INSERT INTO `valor` (`ID_VALOR`, `ID_FACTURA`, `ID_DESTINATARIO`, `VA_RETIRO`, `VA_ESTADO`) VALUES
(1, 2, NULL, '2026-08-18', 'Entregado'),
(4, 38, '1234567890', '2026-08-23', 'Entregado'),
(5, 40, '1234567890', NULL, 'Por Retirar'),
(6, 45, '1122334455', NULL, 'Por Retirar'),
(7, 46, '1122334455', NULL, 'Por Retirar'),
(8, 47, '1234567890', '2026-09-28', 'Entregado');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `abono`
--
ALTER TABLE `abono`
  ADD PRIMARY KEY (`ID_ABONO`);

--
-- Indices de la tabla `bus`
--
ALTER TABLE `bus`
  ADD PRIMARY KEY (`ID_BUS`);

--
-- Indices de la tabla `cierre_caja`
--
ALTER TABLE `cierre_caja`
  ADD PRIMARY KEY (`ID_CIERRE`),
  ADD KEY `ID_USUARIO` (`ID_USUARIO`),
  ADD KEY `ID_SUCURSAL` (`ID_SUCURSAL`);

--
-- Indices de la tabla `cierre_deposito_detalle`
--
ALTER TABLE `cierre_deposito_detalle`
  ADD PRIMARY KEY (`ID_DEPOSITO`),
  ADD KEY `FK_DEPOSITO_CIERRE` (`ID_CIERRE`);

--
-- Indices de la tabla `ciudad`
--
ALTER TABLE `ciudad`
  ADD PRIMARY KEY (`ID_CIUDAD`),
  ADD UNIQUE KEY `idx_ciudad_nombre` (`NOMBRE`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`ID_CLIENTE`);

--
-- Indices de la tabla `corredor`
--
ALTER TABLE `corredor`
  ADD PRIMARY KEY (`ID_CORREDOR`);

--
-- Indices de la tabla `corredor_parada`
--
ALTER TABLE `corredor_parada`
  ADD PRIMARY KEY (`ID_CORREDOR_PARADA`),
  ADD UNIQUE KEY `idx_corredor_orden` (`ID_CORREDOR`,`ORDEN`);

--
-- Indices de la tabla `corredor_tramo_operativo`
--
ALTER TABLE `corredor_tramo_operativo`
  ADD PRIMARY KEY (`ID_CORREDOR_TRAMO`),
  ADD KEY `fk_tramo_corredor` (`ID_CORREDOR`);

--
-- Indices de la tabla `encomienda`
--
ALTER TABLE `encomienda`
  ADD PRIMARY KEY (`ID_ENCOMIENDA`),
  ADD KEY `ID_FACTURA` (`ID_FACTURA`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`ID_FACTURA`),
  ADD KEY `ID_RUTA` (`ID_RUTA`),
  ADD KEY `ID_CLIENTE` (`ID_CLIENTE`),
  ADD KEY `ID_USUARIO` (`ID_USUARIO`),
  ADD KEY `ID_SUCURSAL` (`ID_SUCURSAL`);

--
-- Indices de la tabla `movimiento_caja`
--
ALTER TABLE `movimiento_caja`
  ADD PRIMARY KEY (`ID_MOVIMIENTO`),
  ADD KEY `ID_USUARIO` (`ID_USUARIO`),
  ADD KEY `ID_SUCURSAL` (`ID_SUCURSAL`),
  ADD KEY `ID_FACTURA` (`ID_FACTURA`);

--
-- Indices de la tabla `pasaje`
--
ALTER TABLE `pasaje`
  ADD PRIMARY KEY (`ID_PASAJE`),
  ADD KEY `ID_FACTURA` (`ID_FACTURA`),
  ADD KEY `ID_BUS` (`ID_BUS`);

--
-- Indices de la tabla `pasaje_tramo`
--
ALTER TABLE `pasaje_tramo`
  ADD PRIMARY KEY (`ID_PASAJE_TRAMO`),
  ADD KEY `ID_PASAJE` (`ID_PASAJE`),
  ADD KEY `ID_RUTA_TRAMO` (`ID_RUTA_TRAMO`);

--
-- Indices de la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD PRIMARY KEY (`ID_RESERVA`),
  ADD KEY `ID_FACTURA` (`ID_FACTURA`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`ID_ROL`);

--
-- Indices de la tabla `ruta`
--
ALTER TABLE `ruta`
  ADD PRIMARY KEY (`ID_RUTA`),
  ADD KEY `idx_ruta_corredor` (`ID_CORREDOR`),
  ADD KEY `idx_ruta_origen_destino` (`RUT_ORIGEN`,`RUT_DESTINO`);

--
-- Indices de la tabla `ruta_tramo`
--
ALTER TABLE `ruta_tramo`
  ADD PRIMARY KEY (`ID_RUTA_TRAMO`),
  ADD KEY `ID_RUTA` (`ID_RUTA`);

--
-- Indices de la tabla `sobrepeso`
--
ALTER TABLE `sobrepeso`
  ADD PRIMARY KEY (`ID_SOBREPESO`),
  ADD KEY `ID_FACTURA` (`ID_FACTURA`);

--
-- Indices de la tabla `socio`
--
ALTER TABLE `socio`
  ADD PRIMARY KEY (`ID_SOCIO`),
  ADD KEY `ID_BUS` (`ID_BUS`);

--
-- Indices de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  ADD PRIMARY KEY (`ID_SUCURSAL`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`ID_USUARIO`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`),
  ADD UNIQUE KEY `USUARIO` (`USUARIO`),
  ADD KEY `ID_ROL` (`ID_ROL`),
  ADD KEY `ID_SUCURSAL` (`ID_SUCURSAL`);

--
-- Indices de la tabla `valor`
--
ALTER TABLE `valor`
  ADD PRIMARY KEY (`ID_VALOR`),
  ADD KEY `ID_FACTURA` (`ID_FACTURA`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `abono`
--
ALTER TABLE `abono`
  MODIFY `ID_ABONO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `bus`
--
ALTER TABLE `bus`
  MODIFY `ID_BUS` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cierre_caja`
--
ALTER TABLE `cierre_caja`
  MODIFY `ID_CIERRE` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cierre_deposito_detalle`
--
ALTER TABLE `cierre_deposito_detalle`
  MODIFY `ID_DEPOSITO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `ciudad`
--
ALTER TABLE `ciudad`
  MODIFY `ID_CIUDAD` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `corredor`
--
ALTER TABLE `corredor`
  MODIFY `ID_CORREDOR` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `corredor_parada`
--
ALTER TABLE `corredor_parada`
  MODIFY `ID_CORREDOR_PARADA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `corredor_tramo_operativo`
--
ALTER TABLE `corredor_tramo_operativo`
  MODIFY `ID_CORREDOR_TRAMO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `encomienda`
--
ALTER TABLE `encomienda`
  MODIFY `ID_ENCOMIENDA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `ID_FACTURA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT de la tabla `movimiento_caja`
--
ALTER TABLE `movimiento_caja`
  MODIFY `ID_MOVIMIENTO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de la tabla `pasaje`
--
ALTER TABLE `pasaje`
  MODIFY `ID_PASAJE` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `pasaje_tramo`
--
ALTER TABLE `pasaje_tramo`
  MODIFY `ID_PASAJE_TRAMO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `reserva`
--
ALTER TABLE `reserva`
  MODIFY `ID_RESERVA` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `ID_ROL` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ruta`
--
ALTER TABLE `ruta`
  MODIFY `ID_RUTA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `ruta_tramo`
--
ALTER TABLE `ruta_tramo`
  MODIFY `ID_RUTA_TRAMO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT de la tabla `sobrepeso`
--
ALTER TABLE `sobrepeso`
  MODIFY `ID_SOBREPESO` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `socio`
--
ALTER TABLE `socio`
  MODIFY `ID_SOCIO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  MODIFY `ID_SUCURSAL` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `ID_USUARIO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `valor`
--
ALTER TABLE `valor`
  MODIFY `ID_VALOR` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cierre_caja`
--
ALTER TABLE `cierre_caja`
  ADD CONSTRAINT `cierre_caja_ibfk_1` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`),
  ADD CONSTRAINT `cierre_caja_ibfk_2` FOREIGN KEY (`ID_SUCURSAL`) REFERENCES `sucursal` (`ID_SUCURSAL`);

--
-- Filtros para la tabla `corredor_parada`
--
ALTER TABLE `corredor_parada`
  ADD CONSTRAINT `fk_parada_corredor` FOREIGN KEY (`ID_CORREDOR`) REFERENCES `corredor` (`ID_CORREDOR`) ON DELETE CASCADE;

--
-- Filtros para la tabla `corredor_tramo_operativo`
--
ALTER TABLE `corredor_tramo_operativo`
  ADD CONSTRAINT `fk_tramo_corredor` FOREIGN KEY (`ID_CORREDOR`) REFERENCES `corredor` (`ID_CORREDOR`) ON DELETE CASCADE;

--
-- Filtros para la tabla `encomienda`
--
ALTER TABLE `encomienda`
  ADD CONSTRAINT `encomienda_ibfk_1` FOREIGN KEY (`ID_FACTURA`) REFERENCES `factura` (`ID_FACTURA`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `factura_ibfk_1` FOREIGN KEY (`ID_RUTA`) REFERENCES `ruta` (`ID_RUTA`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `factura_ibfk_2` FOREIGN KEY (`ID_CLIENTE`) REFERENCES `cliente` (`ID_CLIENTE`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `factura_ibfk_3` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `factura_ibfk_4` FOREIGN KEY (`ID_SUCURSAL`) REFERENCES `sucursal` (`ID_SUCURSAL`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `movimiento_caja`
--
ALTER TABLE `movimiento_caja`
  ADD CONSTRAINT `movimiento_caja_ibfk_1` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `movimiento_caja_ibfk_2` FOREIGN KEY (`ID_SUCURSAL`) REFERENCES `sucursal` (`ID_SUCURSAL`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `movimiento_caja_ibfk_3` FOREIGN KEY (`ID_FACTURA`) REFERENCES `factura` (`ID_FACTURA`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pasaje`
--
ALTER TABLE `pasaje`
  ADD CONSTRAINT `pasaje_ibfk_1` FOREIGN KEY (`ID_FACTURA`) REFERENCES `factura` (`ID_FACTURA`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pasaje_ibfk_2` FOREIGN KEY (`ID_BUS`) REFERENCES `bus` (`ID_BUS`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `pasaje_tramo`
--
ALTER TABLE `pasaje_tramo`
  ADD CONSTRAINT `pasaje_tramo_ibfk_1` FOREIGN KEY (`ID_PASAJE`) REFERENCES `pasaje` (`ID_PASAJE`) ON DELETE CASCADE,
  ADD CONSTRAINT `pasaje_tramo_ibfk_2` FOREIGN KEY (`ID_RUTA_TRAMO`) REFERENCES `ruta_tramo` (`ID_RUTA_TRAMO`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD CONSTRAINT `reserva_ibfk_1` FOREIGN KEY (`ID_FACTURA`) REFERENCES `factura` (`ID_FACTURA`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ruta`
--
ALTER TABLE `ruta`
  ADD CONSTRAINT `fk_ruta_corredor` FOREIGN KEY (`ID_CORREDOR`) REFERENCES `corredor` (`ID_CORREDOR`) ON DELETE SET NULL;

--
-- Filtros para la tabla `ruta_tramo`
--
ALTER TABLE `ruta_tramo`
  ADD CONSTRAINT `ruta_tramo_ibfk_1` FOREIGN KEY (`ID_RUTA`) REFERENCES `ruta` (`ID_RUTA`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sobrepeso`
--
ALTER TABLE `sobrepeso`
  ADD CONSTRAINT `sobrepeso_ibfk_1` FOREIGN KEY (`ID_FACTURA`) REFERENCES `factura` (`ID_FACTURA`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `socio`
--
ALTER TABLE `socio`
  ADD CONSTRAINT `socio_ibfk_1` FOREIGN KEY (`ID_BUS`) REFERENCES `bus` (`ID_BUS`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`ID_ROL`) REFERENCES `rol` (`ID_ROL`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`ID_SUCURSAL`) REFERENCES `sucursal` (`ID_SUCURSAL`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `valor`
--
ALTER TABLE `valor`
  ADD CONSTRAINT `valor_ibfk_1` FOREIGN KEY (`ID_FACTURA`) REFERENCES `factura` (`ID_FACTURA`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
