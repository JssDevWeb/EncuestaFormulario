<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Avanzado - Sistema de Encuestas</title>
    <link rel="stylesheet" href="estilos.css">    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #667eea;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-weight: 500;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .chart-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        
        .export-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .export-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .export-btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .export-btn:hover {
            background: #5a67d8;
        }
        
        .export-btn.csv {
            background: #10b981;
        }
        
        .export-btn.csv:hover {
            background: #059669;
        }
        
        .export-btn.pdf {
            background: #ef4444;
        }
        
        .export-btn.pdf:hover {
            background: #dc2626;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .error {
            background: #fef2f2;
            color: #dc2626;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #dc2626;
        }
        
        .form-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .form-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .export-buttons {
                flex-direction: column;
                align-items: center;
            }
              .export-btn {
                width: 200px;
            }
            
            #reset-dashboard:hover {
                background: #d97706 !important;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>📊 Dashboard Avanzado de Encuestas</h1>
            <p>Análisis visual e interactivo de respuestas anónimas</p>
        </div>
          <div class="form-selector">
            <label for="formulario-select">Selecciona un formulario para analizar:</label>
            <select id="formulario-select">
                <option value="">Cargando formularios...</option>
            </select>
            <button id="reset-dashboard" onclick="resetDashboard()" style="margin-left: 10px; padding: 8px 15px; background: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer;">
                🔄 Resetear
            </button>
        </div>
        
        <div id="dashboard-content" style="display: none;">
            <!-- Estadísticas generales -->
            <div class="stats-grid" id="stats-grid">
                <!-- Se llenarán dinámicamente -->
            </div>
            
            <!-- Gráficos -->
            <div class="charts-grid">
                <div class="chart-container">
                    <div class="chart-title">Distribución de Respuestas por Pregunta</div>
                    <canvas id="distributionChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <div class="chart-title">Tendencia Temporal de Respuestas</div>
                    <canvas id="timelineChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <div class="chart-title">Análisis de Satisfacción (Escalas)</div>
                    <canvas id="satisfactionChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <div class="chart-title">Resumen por Tipo de Pregunta</div>
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
            
            <!-- Sección de exportación -->
            <div class="export-section">
                <h3>📋 Exportar Reportes</h3>
                <p>Descarga los datos y gráficos para presentaciones y análisis externos</p>
                <div class="export-buttons">
                    <button class="export-btn csv" onclick="exportToCSV()">
                        📊 Exportar CSV
                    </button>
                    <button class="export-btn pdf" onclick="exportToPDF()">
                        📄 Exportar PDF
                    </button>
                    <button class="export-btn" onclick="exportCharts()">
                        📈 Exportar Gráficos
                    </button>
                </div>
            </div>
        </div>
        
        <div id="loading" class="loading">
            <p>⏳ Cargando datos del dashboard...</p>
        </div>
        
        <div id="error-message" class="error" style="display: none;"></div>
    </div>    <script>
        // Variables globales para los gráficos
        let charts = {};
        let currentFormData = null;
        let isLoading = false;
        let isRendering = false;
        let renderTimeout = null;
        
        // Configuración de colores para consistencia visual
        const colors = {
            primary: '#667eea',
            secondary: '#764ba2',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#3b82f6'
        };
        
        // Paleta de colores para gráficos
        const chartColors = [
            '#667eea', '#10b981', '#f59e0b', '#ef4444', 
            '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
        ];
        
        // Inicializar dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadFormularios();
        });
        
        // Cargar lista de formularios
        async function loadFormularios() {            try {
                const response = await fetch('../backend/listar_formularios.php?format=json');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                const select = document.getElementById('formulario-select');
                select.innerHTML = '<option value="">Selecciona un formulario...</option>';
                
                if (data.success && data.formularios && data.formularios.length > 0) {
                    data.formularios.forEach(form => {
                        const option = document.createElement('option');
                        option.value = form.id;
                        option.textContent = `${form.titulo} (${form.num_respuestas || 0} respuestas)`;
                        select.appendChild(option);
                    });                    // Event listener para selección de formulario
                    select.addEventListener('change', function() {
                        const selectedValue = this.value;
                        
                        if (selectedValue) {
                            // Limpiar dashboard antes de cargar nuevo formulario
                            hideDashboard();
                            // Delay más largo para asegurar que la limpieza se complete
                            setTimeout(() => {
                                if (this.value === selectedValue) { // Verificar que no haya cambiado
                                    loadDashboardData(selectedValue);
                                }
                            }, 200);
                        } else {
                            hideDashboard();
                        }                    });
                    
                    // Auto-seleccionar el primer formulario con respuestas con delay
                    const firstFormWithResponses = data.formularios.find(f => f.num_respuestas > 0);
                    if (firstFormWithResponses) {
                        setTimeout(() => {
                            select.value = firstFormWithResponses.id;
                            loadDashboardData(firstFormWithResponses.id);
                        }, 300);
                    }
                } else {
                    select.innerHTML = '<option value="">No hay formularios disponibles</option>';
                    showError('No se encontraron formularios con respuestas');
                }            } catch (error) {
                console.error('Error loading formularios:', error);
                showError('Error al cargar formularios: ' + error.message);
            }}
          // Cargar datos del dashboard para un formulario específico
        async function loadDashboardData(formularioId) {
            // Prevenir llamadas concurrentes
            if (isLoading) {
                console.log('Ya hay una carga en progreso, ignorando...');
                return;
            }
            
            isLoading = true;
            showLoading();
            
            // Cancelar cualquier renderizado pendiente
            if (renderTimeout) {
                clearTimeout(renderTimeout);
                renderTimeout = null;
            }
            
            try {
                const response = await fetch(`../backend/dashboard_api.php?formulario_id=${formularioId}`);
                const result = await response.json();
                  if (result.success) {
                    currentFormData = result.data;
                    hideLoading();
                    showDashboard();
                    // Pequeño delay para asegurar que el DOM esté listo
                    renderTimeout = setTimeout(() => {
                        renderDashboard(result.data);
                    }, 150);
                } else {
                    showError(result.error || 'Error al cargar datos');
                }
            } catch (error) {
                showError('Error al cargar datos del formulario: ' + error.message);
            } finally {
                isLoading = false;
            }        }
          // Verificar que todos los elementos canvas estén disponibles
        function areCanvasElementsReady() {
            const canvasIds = ['distributionChart', 'timelineChart', 'satisfactionChart', 'typeChart'];
            const dashboardContent = document.getElementById('dashboard-content');
            
            // Verificar que el contenedor principal esté visible
            if (!dashboardContent || dashboardContent.style.display === 'none') {
                console.warn('Dashboard content no está visible');
                return false;
            }
            
            for (const id of canvasIds) {
                const element = document.getElementById(id);
                if (!element) {
                    console.warn(`Elemento canvas ${id} no está disponible`);
                    return false;
                }
                
                // Verificar que el elemento esté realmente en el DOM y visible
                if (!element.offsetParent && element.style.display !== 'none') {
                    console.warn(`Elemento canvas ${id} no es visible en el DOM`);
                    return false;
                }
            }
            return true;
        }// Renderizar todo el dashboard
        function renderDashboard(data, attempt = 0) {
            const maxAttempts = 3; // Reducir intentos para evitar bucles
            
            // Prevenir renderizados concurrentes
            if (isRendering) {
                console.log('Ya hay un renderizado en progreso, ignorando...');
                return;
            }
            
            // Verificar que el dashboard esté visible
            const dashboardContent = document.getElementById('dashboard-content');
            if (!dashboardContent || dashboardContent.style.display === 'none') {
                console.log('Dashboard no está visible, cancelando renderizado');
                return;
            }
            
            if (!areCanvasElementsReady()) {
                if (attempt < maxAttempts) {
                    console.warn(`Elementos canvas no están listos, reintentando en ${200 + (attempt * 100)}ms... (intento ${attempt + 1}/${maxAttempts})`);
                    renderTimeout = setTimeout(() => {
                        renderDashboard(data, attempt + 1);
                    }, 200 + (attempt * 100));
                    return;
                } else {
                    console.error('No se pudieron cargar los elementos canvas después de varios intentos');
                    showError('Error: No se pudieron cargar los gráficos. Intenta seleccionar el formulario de nuevo.');
                    return;
                }
            }
            
            isRendering = true;
            
            try {
                renderStats(data);
                renderDistributionChart(data);
                renderTimelineChart(data);
                renderSatisfactionChart(data);
                renderTypeChart(data);
                console.log('Dashboard renderizado exitosamente');
            } catch (error) {
                console.error('Error durante el renderizado:', error);
                showError('Error al renderizar los gráficos: ' + error.message);
            } finally {
                isRendering = false;
            }
        }
        
        // Renderizar estadísticas generales
        function renderStats(data) {
            const statsGrid = document.getElementById('stats-grid');
            const stats = data.estadisticas_generales;
            
            const statCards = [
                {
                    number: stats.total_respuestas,
                    label: 'Total Respuestas'
                },
                {
                    number: stats.total_preguntas,
                    label: 'Preguntas'
                },
                {
                    number: stats.promedio_satisfaccion > 0 ? stats.promedio_satisfaccion + '/5' : 'N/A',
                    label: 'Satisfacción Promedio'
                },
                {
                    number: stats.tasa_completitud + '%',
                    label: 'Tasa de Completitud'
                }
            ];
            
            statsGrid.innerHTML = statCards.map(stat => `
                <div class="stat-card">
                    <div class="stat-number">${stat.number}</div>
                    <div class="stat-label">${stat.label}</div>
                </div>
            `).join('');        }
          // Gráfico de distribución de respuestas
        function renderDistributionChart(data) {
            const chartElement = document.getElementById('distributionChart');
            if (!chartElement) {
                console.warn('Elemento distributionChart no encontrado en el DOM');
                return;
            }
            const ctx = chartElement.getContext('2d');
            
            // Destruir gráfico anterior si existe
            if (charts.distribution) {
                charts.distribution.destroy();
            }
            
            const labels = data.preguntas.map(p => p.texto.substring(0, 30) + '...');
            const responseCounts = data.preguntas.map(p => p.total_respuestas);
            
            charts.distribution = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Número de Respuestas',
                        data: responseCounts,
                        backgroundColor: chartColors[0],
                        borderColor: chartColors[0],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });        }
          // Gráfico de línea temporal
        function renderTimelineChart(data) {
            const chartElement = document.getElementById('timelineChart');
            if (!chartElement) {
                console.warn('Elemento timelineChart no encontrado en el DOM');
                return;
            }
            const ctx = chartElement.getContext('2d');
            
            if (charts.timeline) {
                charts.timeline.destroy();
            }
            
            // Usar datos reales del timeline
            const timelineData = data.timeline;
            
            if (timelineData.length > 0) {
                charts.timeline = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: timelineData.map(t => t.fecha_formateada),
                        datasets: [{
                            label: 'Respuestas por Día',
                            data: timelineData.map(t => t.count),
                            borderColor: chartColors[1],
                            backgroundColor: chartColors[1] + '20',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            } else {
                // Mostrar mensaje si no hay datos temporales
                ctx.canvas.parentElement.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">No hay datos temporales suficientes</p>';
            }        }
          // Gráfico de satisfacción (escalas)
        function renderSatisfactionChart(data) {
            const chartElement = document.getElementById('satisfactionChart');
            if (!chartElement) {
                console.warn('Elemento satisfactionChart no encontrado en el DOM');
                return;
            }
            const ctx = chartElement.getContext('2d');
            
            if (charts.satisfaction) {
                charts.satisfaction.destroy();
            }
            
            // Buscar preguntas de escala y sus datos
            const scaleQuestions = data.preguntas.filter(p => p.tipo === 'escala' && p.datos.distribucion);
            
            if (scaleQuestions.length > 0) {
                // Agregar todas las distribuciones de escalas
                const satisfactionLevels = {
                    'Muy Insatisfecho (1-2)': 0,
                    'Neutral (3)': 0,
                    'Satisfecho (4-5)': 0
                };
                
                scaleQuestions.forEach(question => {
                    question.datos.distribucion.forEach(item => {
                        const valor = item.valor;
                        if (valor <= 2) {
                            satisfactionLevels['Muy Insatisfecho (1-2)'] += item.count;
                        } else if (valor === 3) {
                            satisfactionLevels['Neutral (3)'] += item.count;
                        } else {
                            satisfactionLevels['Satisfecho (4-5)'] += item.count;
                        }
                    });
                });
                
                const labels = Object.keys(satisfactionLevels);
                const values = Object.values(satisfactionLevels);
                
                if (values.some(v => v > 0)) {
                    charts.satisfaction = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: values,
                                backgroundColor: [chartColors[3], chartColors[2], chartColors[1]]
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                } else {
                    ctx.canvas.parentElement.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">No hay respuestas de escala disponibles</p>';
                }
            } else {
                ctx.canvas.parentElement.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">No hay preguntas de escala en este formulario</p>';
            }        }
          // Gráfico por tipo de pregunta
        function renderTypeChart(data) {
            const chartElement = document.getElementById('typeChart');
            if (!chartElement) {
                console.warn('Elemento typeChart no encontrado en el DOM');
                return;
            }
            const ctx = chartElement.getContext('2d');
            
            if (charts.type) {
                charts.type.destroy();
            }
            
            const typeLabels = {
                'escala': 'Escala (1-5)',
                'texto': 'Texto Libre',
                'seleccion': 'Selección Múltiple'
            };
            
            const labels = [];
            const values = [];
            
            Object.keys(data.tipos_pregunta).forEach(tipo => {
                const count = data.tipos_pregunta[tipo];
                if (count > 0) {
                    labels.push(typeLabels[tipo] || tipo);
                    values.push(count);
                }
            });
            
            if (values.length > 0) {
                charts.type = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: chartColors.slice(0, labels.length)
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }                    }
                });
            } else {
                ctx.canvas.parentElement.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">No hay datos de tipos de pregunta</p>';
            }
        }
        
        // Funciones de exportación
        function exportToCSV() {
            if (!currentFormData) return;
            
            const csvContent = generateCSVContent(currentFormData);
            downloadFile(csvContent, `estadisticas_formulario_${currentFormData.formulario.id}.csv`, 'text/csv');        }
        
        function exportToPDF() {
            if (!currentFormData) return;
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Configuración del documento
            doc.setFontSize(20);
            doc.text('Reporte de Encuesta', 20, 20);
            
            // Información del formulario
            doc.setFontSize(14);
            doc.text(`Formulario: ${currentFormData.formulario.titulo}`, 20, 35);
            doc.text(`Fecha de generación: ${new Date().toLocaleDateString('es-ES')}`, 20, 45);
            
            // Estadísticas generales
            doc.setFontSize(12);
            doc.text('Estadísticas Generales:', 20, 60);
            const stats = currentFormData.estadisticas_generales;
            doc.text(`• Total de respuestas: ${stats.total_respuestas}`, 25, 70);
            doc.text(`• Total de preguntas: ${stats.total_preguntas}`, 25, 80);
            doc.text(`• Satisfacción promedio: ${stats.promedio_satisfaccion > 0 ? stats.promedio_satisfaccion + '/5' : 'N/A'}`, 25, 90);
            doc.text(`• Tasa de completitud: ${stats.tasa_completitud}%`, 25, 100);
            
            // Resumen por pregunta
            let yPos = 120;
            doc.text('Resumen por Pregunta:', 20, yPos);
            yPos += 10;
            
            currentFormData.preguntas.forEach((pregunta, index) => {
                if (yPos > 250) {
                    doc.addPage();
                    yPos = 20;
                }
                
                doc.text(`${index + 1}. ${pregunta.texto.substring(0, 60)}...`, 25, yPos);
                yPos += 8;
                doc.text(`   Tipo: ${pregunta.tipo} | Respuestas: ${pregunta.total_respuestas}`, 30, yPos);
                yPos += 8;
                
                if (pregunta.datos.promedio) {
                    doc.text(`   Promedio: ${pregunta.datos.promedio}`, 30, yPos);
                    yPos += 8;
                }
                yPos += 5;
            });
            
            // Agregar gráficos como imágenes
            if (charts.distribution) {
                doc.addPage();
                doc.text('Gráfico de Distribución', 20, 20);
                const chartImage = charts.distribution.toBase64Image();
                doc.addImage(chartImage, 'PNG', 20, 30, 170, 100);
            }
            
            if (charts.timeline) {
                doc.addPage();
                doc.text('Gráfico Temporal', 20, 20);
                const chartImage = charts.timeline.toBase64Image();
                doc.addImage(chartImage, 'PNG', 20, 30, 170, 100);
            }
            
            // Descargar PDF
            doc.save(`reporte_formulario_${currentFormData.formulario.id}.pdf`);
        }
        
        function exportCharts() {
            // Exportar cada gráfico como imagen
            Object.keys(charts).forEach((chartKey, index) => {
                if (charts[chartKey]) {
                    const link = document.createElement('a');
                    link.download = `grafico_${chartKey}_formulario_${currentFormData.formulario.id}.png`;
                    link.href = charts[chartKey].toBase64Image();
                    link.click();
                }            });
        }
        
        function generateCSVContent(data) {
            let csv = 'Formulario,Pregunta,Tipo,Total Respuestas,Promedio,Detalles\n';
            
            data.preguntas.forEach(pregunta => {
                const promedio = pregunta.datos.promedio || 'N/A';
                const detalles = pregunta.tipo === 'texto' ? 
                    `Promedio longitud: ${pregunta.datos.promedio_longitud} caracteres` :
                    'Ver datos completos en dashboard';
                
                csv += `"${data.formulario.titulo}","${pregunta.texto}","${pregunta.tipo}",${pregunta.total_respuestas},"${promedio}","${detalles}"\n`;
            });
            
            return csv;
        }
        
        function downloadFile(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        }
        
        // Funciones de UI
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('dashboard-content').style.display = 'none';
            document.getElementById('error-message').style.display = 'none';
        }
        
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
        
        function showDashboard() {
            document.getElementById('dashboard-content').style.display = 'block';
        }        function hideDashboard() {
            // Cancelar cualquier renderizado pendiente
            if (renderTimeout) {
                clearTimeout(renderTimeout);
                renderTimeout = null;
            }
            
            // Resetear estados
            isRendering = false;
            isLoading = false;
            
            // Limpiar gráficos antes de ocultar para evitar referencias obsoletas
            if (charts.distribution) {
                charts.distribution.destroy();
                charts.distribution = null;
            }
            if (charts.timeline) {
                charts.timeline.destroy();
                charts.timeline = null;
            }
            if (charts.satisfaction) {
                charts.satisfaction.destroy();
                charts.satisfaction = null;
            }
            if (charts.type) {
                charts.type.destroy();
                charts.type = null;
            }
            
            document.getElementById('dashboard-content').style.display = 'none';
            hideLoading();
        }
        
        function resetDashboard() {
            console.log('🔄 Reseteando dashboard...');
            
            // Cancelar todos los timeouts pendientes
            if (renderTimeout) {
                clearTimeout(renderTimeout);
                renderTimeout = null;
            }
            
            // Resetear todas las variables de estado
            isLoading = false;
            isRendering = false;
            currentFormData = null;
            
            // Limpiar y destruir todos los gráficos
            Object.keys(charts).forEach(key => {
                if (charts[key]) {
                    try {
                        charts[key].destroy();
                    } catch (e) {
                        console.warn(`Error destruyendo gráfico ${key}:`, e);
                    }
                    charts[key] = null;
                }
            });
            
            // Limpiar la interfaz
            hideDashboard();
            document.getElementById('error-message').style.display = 'none';
            
            // Resetear el selector
            const select = document.getElementById('formulario-select');
            select.value = '';
            
            // Recargar la lista de formularios
            setTimeout(() => {
                loadFormularios();
            }, 500);
            
            console.log('✅ Dashboard reseteado correctamente');
        }
        
        function showError(message) {
            document.getElementById('error-message').textContent = message;
            document.getElementById('error-message').style.display = 'block';
            hideLoading();
            hideDashboard();
        }
    </script>
</body>
</html>
