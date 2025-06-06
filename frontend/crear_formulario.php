<?php
/**
 * Interfaz para Crear Formularios
 * Sistema de Encuestas de Satisfacción Anónimas
 * 
 * Frontend que permite crear nuevos formularios con preguntas dinámicas
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Formulario - Encuestas de Satisfacción</title>
    
    <!-- Estilos accesibles -->
    <link rel="stylesheet" href="estilos.css">
    
    <!-- Meta tags para SEO y accesibilidad -->
    <meta name="description" content="Crear nuevo formulario de encuesta de satisfacción anónima">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <header role="banner">
            <h1>➕ Crear Nuevo Formulario</h1>
            <p>Diseña un formulario personalizado para recopilar feedback anónimo</p>
        </header>

        <!-- Navegación -->
        <nav role="navigation" aria-label="Navegación principal">
            <div class="nav-links" style="margin-bottom: 2rem;">
                <a href="index_admin.php" class="btn btn-secondary">
                    ← Volver al Panel
                </a>
                <a href="#vista-previa" class="btn btn-info" id="btn-vista-previa" style="display: none;">
                    👁️ Vista Previa
                </a>
            </div>
        </nav>

        <!-- Mensajes de estado -->
        <div id="mensajes" aria-live="polite"></div>

        <!-- Formulario principal -->
        <main role="main">
            <form id="formulario-creacion" novalidate>
                <!-- Información básica del formulario -->
                <section aria-labelledby="info-basica-title">
                    <h2 id="info-basica-title">📋 Información Básica</h2>
                    
                    <div class="form-group">
                        <label for="titulo" class="required">Título del Formulario:</label>
                        <input 
                            type="text" 
                            id="titulo" 
                            name="titulo" 
                            required 
                            maxlength="255"
                            aria-describedby="titulo-help titulo-error"
                            placeholder="Ej: Encuesta de Satisfacción - Servicio al Cliente"
                        >
                        <div id="titulo-help" class="help-text">
                            Un título claro y descriptivo ayuda a los usuarios a entender el propósito del formulario
                        </div>
                        <div id="titulo-error" class="error-message" role="alert"></div>
                    </div>

                    <div class="form-group">
                        <label for="descripcion" class="required">Descripción:</label>
                        <textarea 
                            id="descripcion" 
                            name="descripcion" 
                            required 
                            maxlength="1000"
                            rows="4"
                            aria-describedby="descripcion-help descripcion-error"
                            placeholder="Describe el propósito del formulario y cómo se utilizarán las respuestas..."
                        ></textarea>
                        <div id="descripcion-help" class="help-text">
                            Explica el contexto y objetivo del formulario. Esta información se mostrará a los encuestados.
                        </div>
                        <div id="descripcion-error" class="error-message" role="alert"></div>
                    </div>
                </section>

                <!-- Sección de preguntas -->
                <section aria-labelledby="preguntas-title">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2 id="preguntas-title">❓ Preguntas del Formulario</h2>
                        <button type="button" id="agregar-pregunta" class="btn btn-success">
                            ➕ Agregar Pregunta
                        </button>
                    </div>

                    <div id="contenedor-preguntas" aria-live="polite">
                        <!-- Las preguntas se agregarán dinámicamente aquí -->
                    </div>

                    <div id="preguntas-vacio" class="empty-state" style="text-align: center; padding: 2rem; color: #666;">
                        <p>📝 No hay preguntas agregadas aún</p>
                        <p>Haz clic en "Agregar Pregunta" para comenzar</p>
                    </div>
                </section>

                <!-- Botones de acción -->
                <section style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
                    <div class="form-actions">
                        <button type="button" id="guardar-borrador" class="btn btn-secondary">
                            💾 Guardar Borrador
                        </button>
                        <button type="submit" id="crear-formulario" class="btn btn-primary">
                            ✅ Crear Formulario
                        </button>
                        <button type="button" id="limpiar-form" class="btn btn-warning">
                            🔄 Limpiar Todo
                        </button>
                    </div>
                </section>
            </form>
        </main>

        <!-- Modal de vista previa -->
        <div id="modal-vista-previa" class="modal" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modal-title">👁️ Vista Previa del Formulario</h3>
                    <button type="button" class="modal-close" aria-label="Cerrar vista previa">&times;</button>
                </div>
                <div class="modal-body" id="vista-previa-contenido">
                    <!-- Contenido de vista previa -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-close">Cerrar</button>
                </div>
            </div>
        </div>

        <!-- Información sobre tipos de preguntas -->
        <aside role="complementary" style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <h3>📚 Tipos de Preguntas Disponibles</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div>
                    <h4>✏️ Texto Libre</h4>
                    <p>Permite respuestas abiertas y comentarios detallados</p>
                </div>
                <div>
                    <h4>📊 Escala de Valoración</h4>
                    <p>Calificación numérica (ej. del 1 al 5)</p>
                </div>
                <div>
                    <h4>☑️ Selección</h4>
                    <p>Opciones predefinidas de respuesta</p>
                </div>
            </div>
        </aside>
    </div>

    <!-- Template para nuevas preguntas -->
    <template id="template-pregunta">
        <div class="pregunta-item" data-pregunta-id="">
            <div class="pregunta-header">
                <h4>Pregunta <span class="numero-pregunta"></span></h4>
                <div class="pregunta-actions">
                    <button type="button" class="btn-icon btn-move-up" title="Mover hacia arriba" aria-label="Mover pregunta hacia arriba">↑</button>
                    <button type="button" class="btn-icon btn-move-down" title="Mover hacia abajo" aria-label="Mover pregunta hacia abajo">↓</button>
                    <button type="button" class="btn-icon btn-delete" title="Eliminar pregunta" aria-label="Eliminar pregunta">🗑️</button>
                </div>
            </div>

            <div class="pregunta-content">
                <div class="form-group">
                    <label class="required">Texto de la pregunta:</label>
                    <input 
                        type="text" 
                        name="pregunta-texto" 
                        required 
                        maxlength="500"
                        placeholder="Escribe tu pregunta aquí..."
                        class="pregunta-texto-input"
                    >
                    <div class="error-message" role="alert"></div>
                </div>

                <div class="form-group">
                    <label>Tipo de respuesta:</label>
                    <select name="pregunta-tipo" class="pregunta-tipo-select">
                        <option value="texto">✏️ Texto libre</option>
                        <option value="escala">📊 Escala de valoración</option>
                        <option value="seleccion">☑️ Selección múltiple</option>
                    </select>
                </div>

                <div class="configuracion-tipo" style="display: none;">
                    <!-- Configuraciones específicas por tipo se agregarán aquí -->
                </div>
            </div>
        </div>
    </template>

    <!-- Scripts -->
    <script src="script.js"></script>
    <script>
        /**
         * Funcionalidad específica para crear formularios
         */
        class CreadorFormularios {
            constructor() {
                this.contadorPreguntas = 0;
                this.preguntas = [];
                this.inicializar();
            }

            inicializar() {
                this.configurarEventos();
                this.cargarBorrador();
            }

            configurarEventos() {
                // Botón agregar pregunta
                document.getElementById('agregar-pregunta').addEventListener('click', () => {
                    this.agregarPregunta();
                });

                // Envío del formulario
                document.getElementById('formulario-creacion').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.enviarFormulario();
                });

                // Limpiar formulario
                document.getElementById('limpiar-form').addEventListener('click', () => {
                    this.limpiarFormulario();
                });

                // Guardar borrador
                document.getElementById('guardar-borrador').addEventListener('click', () => {
                    this.guardarBorrador();
                });

                // Vista previa
                document.getElementById('btn-vista-previa').addEventListener('click', () => {
                    this.mostrarVistaPrevia();
                });

                // Validación en tiempo real
                document.getElementById('titulo').addEventListener('input', this.validarTitulo);
                document.getElementById('descripcion').addEventListener('input', this.validarDescripcion);
            }

            agregarPregunta() {
                this.contadorPreguntas++;
                const template = document.getElementById('template-pregunta');
                const clone = template.content.cloneNode(true);
                
                // Configurar la pregunta
                const preguntaItem = clone.querySelector('.pregunta-item');
                preguntaItem.setAttribute('data-pregunta-id', this.contadorPreguntas);
                
                // Actualizar número
                clone.querySelector('.numero-pregunta').textContent = this.contadorPreguntas;
                
                // Configurar eventos
                this.configurarEventosPregunta(clone);
                
                // Agregar al contenedor
                document.getElementById('contenedor-preguntas').appendChild(clone);
                
                // Ocultar mensaje vacío y mostrar vista previa
                document.getElementById('preguntas-vacio').style.display = 'none';
                document.getElementById('btn-vista-previa').style.display = 'inline-block';
                
                // Anunciar a lectores de pantalla
                this.anunciar(`Pregunta ${this.contadorPreguntas} agregada`);
                
                this.actualizarNumeracion();
            }

            configurarEventosPregunta(preguntaElement) {
                // Eliminar pregunta
                preguntaElement.querySelector('.btn-delete').addEventListener('click', (e) => {
                    this.eliminarPregunta(e.target.closest('.pregunta-item'));
                });

                // Mover pregunta
                preguntaElement.querySelector('.btn-move-up').addEventListener('click', (e) => {
                    this.moverPregunta(e.target.closest('.pregunta-item'), 'up');
                });

                preguntaElement.querySelector('.btn-move-down').addEventListener('click', (e) => {
                    this.moverPregunta(e.target.closest('.pregunta-item'), 'down');
                });

                // Cambio de tipo
                preguntaElement.querySelector('.pregunta-tipo-select').addEventListener('change', (e) => {
                    this.configurarTipoPregunta(e.target.closest('.pregunta-item'), e.target.value);
                });

                // Validación de texto
                preguntaElement.querySelector('.pregunta-texto-input').addEventListener('input', (e) => {
                    this.validarTextoPregunta(e.target);
                });
            }

            eliminarPregunta(preguntaItem) {
                if (confirm('¿Estás seguro de que quieres eliminar esta pregunta?')) {
                    preguntaItem.remove();
                    this.actualizarNumeracion();
                    
                    // Si no quedan preguntas, mostrar mensaje vacío
                    const preguntas = document.querySelectorAll('.pregunta-item');
                    if (preguntas.length === 0) {
                        document.getElementById('preguntas-vacio').style.display = 'block';
                        document.getElementById('btn-vista-previa').style.display = 'none';
                    }
                    
                    this.anunciar('Pregunta eliminada');
                }
            }

            moverPregunta(preguntaItem, direccion) {
                const contenedor = document.getElementById('contenedor-preguntas');
                const preguntas = Array.from(contenedor.children);
                const indiceActual = preguntas.indexOf(preguntaItem);
                
                if (direccion === 'up' && indiceActual > 0) {
                    contenedor.insertBefore(preguntaItem, preguntas[indiceActual - 1]);
                } else if (direccion === 'down' && indiceActual < preguntas.length - 1) {
                    contenedor.insertBefore(preguntas[indiceActual + 1], preguntaItem);
                }
                
                this.actualizarNumeracion();
            }

            actualizarNumeracion() {
                const preguntas = document.querySelectorAll('.pregunta-item');
                preguntas.forEach((pregunta, index) => {
                    pregunta.querySelector('.numero-pregunta').textContent = index + 1;
                });
            }

            async enviarFormulario() {
                // Validar formulario
                if (!this.validarFormulario()) {
                    return;
                }

                // Recopilar datos
                const datos = this.recopilarDatos();
                
                // Mostrar loading
                this.mostrarLoading(true);

                try {
                    const response = await fetch('../backend/crear_formulario.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(datos)
                    });

                    const resultado = await response.json();

                    if (resultado.exito) {
                        this.mostrarMensaje('✅ Formulario creado exitosamente', 'success');
                        
                        // Limpiar borrador
                        localStorage.removeItem('formulario_borrador');
                        
                        // Redirigir después de un momento
                        setTimeout(() => {
                            window.location.href = `index_admin.php?mensaje=Formulario creado exitosamente`;
                        }, 2000);
                    } else {
                        this.mostrarMensaje('❌ ' + resultado.errores.join(', '), 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.mostrarMensaje('❌ Error de conexión. Verifica que el servidor esté funcionando.', 'error');
                } finally {
                    this.mostrarLoading(false);
                }
            }

            validarFormulario() {
                let esValido = true;

                // Validar título
                if (!this.validarTitulo()) esValido = false;

                // Validar descripción
                if (!this.validarDescripcion()) esValido = false;

                // Validar preguntas
                const preguntas = document.querySelectorAll('.pregunta-item');
                if (preguntas.length === 0) {
                    this.mostrarMensaje('❌ Debes agregar al menos una pregunta', 'error');
                    esValido = false;
                }

                preguntas.forEach(pregunta => {
                    const textoInput = pregunta.querySelector('.pregunta-texto-input');
                    if (!this.validarTextoPregunta(textoInput)) {
                        esValido = false;
                    }
                });

                return esValido;
            }

            validarTitulo() {
                const titulo = document.getElementById('titulo');
                const error = document.getElementById('titulo-error');
                const valor = titulo.value.trim();

                if (valor.length < 3) {
                    error.textContent = 'El título debe tener al menos 3 caracteres';
                    titulo.setAttribute('aria-invalid', 'true');
                    return false;
                }

                if (valor.length > 255) {
                    error.textContent = 'El título no puede exceder 255 caracteres';
                    titulo.setAttribute('aria-invalid', 'true');
                    return false;
                }

                error.textContent = '';
                titulo.setAttribute('aria-invalid', 'false');
                return true;
            }

            validarDescripcion() {
                const descripcion = document.getElementById('descripcion');
                const error = document.getElementById('descripcion-error');
                const valor = descripcion.value.trim();

                if (valor.length < 10) {
                    error.textContent = 'La descripción debe tener al menos 10 caracteres';
                    descripcion.setAttribute('aria-invalid', 'true');
                    return false;
                }

                if (valor.length > 1000) {
                    error.textContent = 'La descripción no puede exceder 1000 caracteres';
                    descripcion.setAttribute('aria-invalid', 'true');
                    return false;
                }

                error.textContent = '';
                descripcion.setAttribute('aria-invalid', 'false');
                return true;
            }

            validarTextoPregunta(input) {
                const error = input.parentNode.querySelector('.error-message');
                const valor = input.value.trim();

                if (valor.length < 5) {
                    error.textContent = 'La pregunta debe tener al menos 5 caracteres';
                    input.setAttribute('aria-invalid', 'true');
                    return false;
                }

                if (valor.length > 500) {
                    error.textContent = 'La pregunta no puede exceder 500 caracteres';
                    input.setAttribute('aria-invalid', 'true');
                    return false;
                }

                error.textContent = '';
                input.setAttribute('aria-invalid', 'false');
                return true;
            }

            recopilarDatos() {
                const datos = {
                    titulo: document.getElementById('titulo').value.trim(),
                    descripcion: document.getElementById('descripcion').value.trim(),
                    preguntas: []
                };

                const preguntas = document.querySelectorAll('.pregunta-item');
                preguntas.forEach(pregunta => {
                    const texto = pregunta.querySelector('.pregunta-texto-input').value.trim();
                    const tipo = pregunta.querySelector('.pregunta-tipo-select').value;

                    datos.preguntas.push({
                        texto: texto,
                        tipo: tipo
                    });
                });

                return datos;
            }

            guardarBorrador() {
                const datos = this.recopilarDatos();
                localStorage.setItem('formulario_borrador', JSON.stringify(datos));
                this.mostrarMensaje('💾 Borrador guardado', 'info');
            }

            cargarBorrador() {
                const borrador = localStorage.getItem('formulario_borrador');
                if (borrador) {
                    try {
                        const datos = JSON.parse(borrador);
                        
                        // Cargar datos básicos
                        document.getElementById('titulo').value = datos.titulo || '';
                        document.getElementById('descripcion').value = datos.descripcion || '';
                        
                        // Cargar preguntas
                        if (datos.preguntas && datos.preguntas.length > 0) {
                            datos.preguntas.forEach(pregunta => {
                                this.agregarPregunta();
                                const ultimaPregunta = document.querySelector('.pregunta-item:last-child');
                                ultimaPregunta.querySelector('.pregunta-texto-input').value = pregunta.texto;
                                ultimaPregunta.querySelector('.pregunta-tipo-select').value = pregunta.tipo;
                            });
                            
                            this.mostrarMensaje('📋 Borrador cargado', 'info');
                        }
                    } catch (error) {
                        console.error('Error cargando borrador:', error);
                    }
                }
            }

            limpiarFormulario() {
                if (confirm('¿Estás seguro de que quieres limpiar todo el formulario?')) {
                    document.getElementById('formulario-creacion').reset();
                    document.getElementById('contenedor-preguntas').innerHTML = '';
                    document.getElementById('preguntas-vacio').style.display = 'block';
                    document.getElementById('btn-vista-previa').style.display = 'none';
                    this.contadorPreguntas = 0;
                    localStorage.removeItem('formulario_borrador');
                    this.mostrarMensaje('🔄 Formulario limpiado', 'info');
                }
            }

            mostrarMensaje(mensaje, tipo) {
                const contenedor = document.getElementById('mensajes');
                contenedor.innerHTML = `<div class="message message-${tipo}" role="alert">${mensaje}</div>`;
                
                // Auto-ocultar después de unos segundos
                setTimeout(() => {
                    contenedor.innerHTML = '';
                }, 5000);
            }

            mostrarLoading(mostrar) {
                const boton = document.getElementById('crear-formulario');
                if (mostrar) {
                    boton.disabled = true;
                    boton.innerHTML = '⏳ Creando...';
                } else {
                    boton.disabled = false;
                    boton.innerHTML = '✅ Crear Formulario';
                }
            }

            anunciar(mensaje) {
                const announcer = document.createElement('div');
                announcer.setAttribute('aria-live', 'polite');
                announcer.setAttribute('class', 'sr-only');
                announcer.textContent = mensaje;
                document.body.appendChild(announcer);
                
                setTimeout(() => announcer.remove(), 2000);
            }
        }

        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', () => {
            new CreadorFormularios();
        });
    </script>
</body>
</html>
