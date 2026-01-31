<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ficha de Pre-Inscripción</title>
    {{-- <link rel="stylesheet" href="{{ public_path('css/pdf/preinscripcion.css') }}"> --}}

    <style>
        {!! file_get_contents(public_path('css/pdf/preinscripcion.css')) !!}
    </style>




</head>

<body>

    {{-- Marca de agua opcional (correo) --}}
    @if (isset($version) && $version === 'correo')
        <div class="marca-agua">PRE-INSCRIPCIÓN</div>
    @endif

    <div class="container">

        <div class="documento-tipo">
            Documento Institucional – Uso Administrativo
        </div>




        <table class="header-table">
            <tr>
                <td class="header-logo-cell">
                    <img src="{{ public_path('img/logito.png') }}" class="header-logo" alt="Logo">
                </td>

                <td class="header-texto-cell">
                    <h1> IESTP -MANUEL NUÑEZ BUTRON - JULIACA</h1>
                    <h2>Dirección de Admisión</h2>
                    <h3>FICHA DE PREINSCRIPCIÓN</h3>
                    <p class="texto1">EXAMEN EXTRAORDINARIO 2026-I PRIMERA FASE</p>
                </td>

                <td class="header-logo-cell">
                    <img src="{{ public_path('img/logito_robot.png') }}" class="header-logo" alt="Robot">
                </td>
            </tr>
        </table>




        <!-- Foto -->
        <div class="foto-cell">
            <p class="fotito">FOTO<br>3x4 cm</p>
        </div>

        <div class="aviso-institucional">
            <p>Estimado postulante, al final de la presente ficha de preinscripción verá los pasos que debe completar
                para obtener su
                <strong>CONSTANCIA DE INSCRIPCIÓN</strong>
            </p>.<br>
            <span
                style="
                            display:inline-block;
                            margin-top:2px;
                            padding:6px 14px;
                            border:1.5px dashed #2c3e50;
                            letter-spacing:1px;
                        ">
                {{ $preinscripcion->codigo_seguridad }}
            </span>
        </div>
        <!-- DECLARACIÓN JURADA -->
        <div class="section-title">DECLARACIÓN JURADA</div>


        <table>
            <tbody>

                <tr>
                    <td class="label-cell">{{ $preinscripcion->tiene_dni == 'SI' ? 'SI' : 'NO' }}</td>
                    <td class="value-cell">Cuenta con Documento Nacional de Identidad (DNI) vigente</td>
                </tr>
                <tr>
                    <td class="label-cell">{{ $preinscripcion->tiene_certificado_estudios == 'SI' ? 'SI' : 'NO' }}</td>
                    <td class="value-cell">Cuenta con certificado de estudios original que acredite la culminación de la
                        educación secundaria</td>
                </tr>
                <tr>
                    <td class="label-cell">{{ $preinscripcion->cursara_5to_anio == 'SI' ? 'SI' : 'NO' }}</td>
                    <td class="value-cell">Declara que culminará la educación secundaria durante el año 2026 o posterior
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- DATOS GENERALES -->
        <div class="section-title">DATOS GENERALES</div>
        <table>
            <tbody>
                <tr>
                    <td class="label-cell">Escuela Profesional</td>
                    <td class="value-cell">{{ $preinscripcion->escuela_profesional }}</td>
                </tr>
                <tr>
                    <td class="label-cell">D.N.I</td>
                    <td class="value-cell">{{ $preinscripcion->numero_documento }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Apellido paterno</td>
                    <td class="value-cell">{{ $preinscripcion->apellido_paterno }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Apellido materno</td>
                    <td class="value-cell">{{ $preinscripcion->apellido_materno }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Nombres</td>
                    <td class="value-cell">{{ $preinscripcion->nombres }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Género</td>
                    <td class="value-cell">{{ $preinscripcion->genero }}</td>
                </tr>
            </tbody>
        </table>

        <!-- DATOS DE INFORMACIÓN -->
        <div class="section-title">DATOS DE INFORMACIÓN</div>
        <table>
            <tbody>
                <tr>
                    <td class="label-cell">Fecha de nacimiento</td>
                    <td class="value-cell">{{ $preinscripcion->fecha_nacimiento->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <td class="label-cell">País / nacionalidad</td>
                    <td class="value-cell">{{ $preinscripcion->pais_residencia }} -
                        {{ $preinscripcion->nacionalidad ?? 'Peruano' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Ubigeo de nacimiento</td>
                    <td class="value-cell">
                        {{ $preinscripcion->ubigeo_nacimiento }} -
                        {{ $preinscripcion->departamento_nacimiento_nombre }} -
                        {{ $preinscripcion->provincia_nacimiento_nombre }} -
                        {{ $preinscripcion->distrito_nacimiento_nombre }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">Identidad étnica</td>
                    <td class="value-cell">{{ $preinscripcion->identidad_etnica }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Lengua materna</td>
                    <td class="value-cell">{{ $preinscripcion->lengua_materna }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Discapacidad</td>
                    <td class="value-cell">{{ $preinscripcion->tiene_conadis == 'SI' ? 'Sí' : 'Ninguna' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- DATOS DE CONTACTO -->
        <div class="section-title">DATOS DE CONTACTO</div>
        <table>
            <tbody>
                <tr>
                    <td class="label-cell">Celular personal</td>
                    <td class="value-cell">{{ $preinscripcion->celular_personal }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Celular del apoderado</td>
                    <td class="value-cell">{{ $preinscripcion->celular_apoderado }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Correo personal</td>
                    <td class="value-cell">{{ $preinscripcion->correo_electronico }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Ubigeo de residencia</td>
                    <td class="value-cell">
                        {{ $preinscripcion->ubigeo_residencia }} -
                        {{ $preinscripcion->departamento_residencia_nombre }} -
                        {{ $preinscripcion->provincia_residencia_nombre }} -
                        {{ $preinscripcion->distrito_residencia_nombre }}
                    </td>
                </tr>
                <tr>
                    <td class="label-cell">Dirección</td>
                    <td class="value-cell">{{ $preinscripcion->direccion_completa }}</td>
                </tr>
            </tbody>
        </table>

        <!-- DATOS DEL COLEGIO -->
        <div class="section-title">DATOS DEL COLEGIO</div>
        <table>
            <tbody>
                <tr>
                    <td class="label-cell">Nombre del colegio</td>
                    <td class="value-cell">{{ $preinscripcion->nombre_colegio }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Lugar del colegio</td>
                    <td class="value-cell">
                        {{-- {{ $preinscripcion->departamento_colegio }}-
                        {{ $preinscripcion->provincia_colegio }} - --}}
                        {{ $preinscripcion->distrito_colegio }} |
                        {{ $preinscripcion->departamento_colegio_nombre }} -
                        {{ $preinscripcion->provincia_colegio_nombre }} -
                        {{ $preinscripcion->distrito_colegio_nombre }}
                    </td>
                </tr>
                <tr>
                    <td class="label-cell">Gestión dependencia</td>
                    <td class="value-cell">{{ $preinscripcion->gestion_dependencia ?? 'Pública - Sector Educación' }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- DATOS DE UNIVERSIDAD -->
        <div class="section-title">DATOS DE UNIVERSIDAD</div>
        <table>
            <tbody>
                <tr>
                    <td class="label-cell">Universidad</td>
                    <td class="value-cell">{{ $preinscripcion->universidad ?? 'Ninguna' }}</td>
                </tr>
            </tbody>
        </table>


        <!-- pagos a  realizar -->
        <div class="section-title">PAGOS A REALIZAR</div>
        <table>
            <tbody>
                <tr>
                    <td class="label-cell">Pago de inscripción</td>
                    <td class="value-cell">derecho de inscripción S/ 200.00 </td>
                </tr>
            </tbody>
        </table>




        <!--pasos para realizar el pago -->
        <div class="section-title">PASOS PARA REALIZAR SU INSCRIPCIÓN</div>

        <table class="tabla-pasos">
            <tbody>

                <!-- PASO 1 -->
                <tr>
                    <td class="paso-numero">PASO 1</td>
                    <td class="paso-texto">
                        Pago por derecho de inscripción en la caja de la IESTP-Manuel Nuñez Butron o Banco de la Nación.
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="paso-imagen-cell">
                        <img src="{{ public_path('img/pago_cuenta.png') }}" alt="Número de cuenta">
                    </td>
                </tr>

                <!-- PASO 2 -->
                <tr>
                    <td class="paso-numero">PASO 2</td>
                    <td class="paso-texto">
                        Verificación de datos y orientación en la Sede las Carmelitas.
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="paso-imagen-cell2">
                        <img src="{{ public_path('img/direccion.png') }}" alt="Dirección">
                    </td>
                </tr>

                <!-- PASO 3 -->
                <tr>
                    <td class="paso-numero">PASO 3</td>
                    <td class="paso-texto">
                        Captura de fotografía oficial para validación de identidad.
                    </td>
                </tr>

                <!-- PASO 4 -->
                <tr>
                    <td class="paso-numero">PASO 4</td>
                    <td class="paso-texto">
                        Control biométrico mediante toma de huellas dactilares.
                    </td>
                </tr>

                <!-- PASO 5 -->
                <tr>
                    <td class="paso-numero">PASO 5</td>
                    <td class="paso-texto">
                        Revisión de datos y finalización de la inscripción oficial. Recuerda traer: DNI original vigente
                        y una copia
                        simple. Certificado de estudios original y visado por la UGEL. Recibo de pago por derecho de
                        inscripción
                    </td>
                </tr>

            </tbody>
        </table>




        <p class="fecha-emision">
            {{-- Fecha y hora de emisión: {{ now()->format('d/m/Y, h:i:s a') }} --}}
            Fecha y hora de emisión: {{ now()->timezone('America/Lima')->format(' d/m/Y, H:i:s a') }}
        </p>



        {{-- 
            <!-- Instrucciones -->
            <div class="instrucciones">
                <h3>📋 PRÓXIMOS PASOS:</h3>
                <ol>
                    <li>Presente esta ficha impresa junto con los documentos requeridos</li>
                    <li>Realice el pago por derecho de inscripción en la tesorería del instituto</li>
                    @if ($preinscripcion->cursara_5to_anio === 'SI')
                        <li><strong>IMPORTANTE:</strong> Debe presentar carta de compromiso de su colegio</li>
                    @endif
                    <li>Acérquese a la Sede Santa Catalina con todos los documentos</li>
                    <li>Recuerde traer su DNI original y una copia</li>
                </ol>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="firma-linea"></div>
                <p><strong>FIRMA DEL POSTULANTE</strong></p>
                <p style="margin-top: 10px; font-size: 12px; color: #555;">
                    Fecha de registro: {{ now()->timezone('America/Lima')->format('d/m/Y H:i') }}<br />
                    Estado: {{ $preinscripcion->estado ?? 'Pendiente' }}<br />
                    Este documento es válido solo si está acompañado del DNI original del postulante.
                </p>
            </div> --}}

    </div>
</body>


</html>
