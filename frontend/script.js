/**
 * Sistema de Encuestas de Satisfacción Anónimas
 * JavaScript Vanilla - Interactividad y UX
 * 
 * Principios implementados del README.md:
 * - Validación en línea con mensajes específicos
 * - Anonimato preservado (sin tracking de usuario)
 * - Accesibilidad con ARIA y focus management
 * - Progressive enhancement
 */

'use strict';

/**
 * Utilidades para manipulación del DOM y accesibilidad
 */
const DOMUtils = {
    /**
     * Selector seguro que maneja elementos nulos
     */
    $(selector) {
        return document.querySelector(selector);
    },
    
    /**
     * Selector múltiple
     */
    $$(selector) {
        return Array.from(document.querySelectorAll(selector));
    },
    
    /**
     * Crear elemento con atributos
     */
    createElement(tag, attributes = {}, content = '') {
        const element = document.createElement(tag);
        Object.entries(attributes).forEach(([key, value]) => {
            element.setAttribute(key, value);
        });
        if (content) element.textContent = content;
        return element;
    },
    
    /**
     * Mostrar/ocultar elemento con accesibilidad
     */
    toggleVisibility(element, show) {
        if (show) {
            element.style.display = '';
            element.removeAttribute('aria-hidden');
        } else {
            element.style.display = 'none';
            element.setAttribute('aria-hidden', 'true');
        }
    },
    
    /**
     * Focus accesible con scroll suave
     */
    focusElement(element) {
        element.focus();
        element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
};

/**
 * Gestor de validaciones de formulario
 * Implementa validación en línea según principios UX del README
 */
class FormValidator {
    constructor(form) {
        this.form = form;
        this.errors = new Map();
        this.setupValidation();
    }
    
    /**
     * Configura validación en tiempo real
     */
    setupValidation() {
        // Validación en blur para campos de texto
        DOMUtils.$$('input[type="text"], textarea', this.form).forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
        
        // Validación inmediata para radio buttons
        DOMUtils.$$('input[type="radio"]', this.form).forEach(input => {
            input.addEventListener('change', () => this.validateRadioGroup(input));
        });
        
        // Validación en submit
        this.form.addEventListener('submit', (e) => this.validateForm(e));
    }
    
    /**
     * Valida un campo individual
     */
    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        const isRequired = field.hasAttribute('required');
        
        // Limpiar error previo
        this.clearFieldError(field);
        
        // Validación según tipo de campo
        if (isRequired && !value) {
            this.addFieldError(field, 'Este campo es obligatorio');
            return false;
        }
        
        // Validaciones específicas
        if (field.type === 'email' && value && !this.isValidEmail(value)) {
            this.addFieldError(field, 'Ingresa un email válido');
            return false;
        }
        
        if (field.tagName === 'TEXTAREA' && value.length > 500) {
            this.addFieldError(field, 'El comentario no puede exceder 500 caracteres');
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida grupo de radio buttons
     */
    validateRadioGroup(radioInput) {
        const groupName = radioInput.name;
        const group = DOMUtils.$$(`input[name="${groupName}"]`, this.form);
        const isRequired = group.some(radio => radio.hasAttribute('required'));
        const hasSelection = group.some(radio => radio.checked);
        
        this.clearRadioGroupError(groupName);
        
        if (isRequired && !hasSelection) {
            this.addRadioGroupError(groupName, 'Debes seleccionar una opción');
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida formulario completo
     */
    validateForm(event) {
        let isValid = true;
        
        // Validar todos los campos de texto
        DOMUtils.$$('input[type="text"], input[type="email"], textarea', this.form).forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        // Validar grupos de radio buttons
        const radioGroups = new Set();
        DOMUtils.$$('input[type="radio"]', this.form).forEach(radio => {
            radioGroups.add(radio.name);
        });
        
        radioGroups.forEach(groupName => {
            const firstRadio = DOMUtils.$(`input[name="${groupName}"]`, this.form);
            if (!this.validateRadioGroup(firstRadio)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            event.preventDefault();
            this.focusFirstError();
            this.announceErrors();
        }
        
        return isValid;
    }
    
    /**
     * Añade error a un campo
     */
    addFieldError(field, message) {
        const container = field.closest('.form-group');
        if (!container) return;
        
        container.classList.add('has-error');
        
        const errorElement = DOMUtils.createElement('span', {
            'class': 'error',
            'role': 'alert',
            'aria-live': 'polite'
        }, message);
        
        container.appendChild(errorElement);
        this.errors.set(field.name, message);
    }
    
    /**
     * Añade error a grupo de radio buttons
     */
    addRadioGroupError(groupName, message) {
        const firstRadio = DOMUtils.$(`input[name="${groupName}"]`);
        const fieldset = firstRadio.closest('fieldset') || firstRadio.closest('.form-group');
        if (!fieldset) return;
        
        fieldset.classList.add('has-error');
        
        const errorElement = DOMUtils.createElement('span', {
            'class': 'error',
            'role': 'alert',
            'aria-live': 'polite'
        }, message);
        
        fieldset.appendChild(errorElement);
        this.errors.set(groupName, message);
    }
    
    /**
     * Limpia error de un campo
     */
    clearFieldError(field) {
        const container = field.closest('.form-group');
        if (!container) return;
        
        container.classList.remove('has-error');
        const errorElement = container.querySelector('.error');
        if (errorElement) {
            errorElement.remove();
        }
        this.errors.delete(field.name);
    }
    
    /**
     * Limpia error de grupo de radio buttons
     */
    clearRadioGroupError(groupName) {
        const firstRadio = DOMUtils.$(`input[name="${groupName}"]`);
        const fieldset = firstRadio.closest('fieldset') || firstRadio.closest('.form-group');
        if (!fieldset) return;
        
        fieldset.classList.remove('has-error');
        const errorElement = fieldset.querySelector('.error');
        if (errorElement) {
            errorElement.remove();
        }
        this.errors.delete(groupName);
    }
    
    /**
     * Enfoca el primer campo con error
     */
    focusFirstError() {
        const firstErrorField = DOMUtils.$('.has-error input, .has-error textarea', this.form);
        if (firstErrorField) {
            DOMUtils.focusElement(firstErrorField);
        }
    }
    
    /**
     * Anuncia errores para lectores de pantalla
     */
    announceErrors() {
        const errorCount = this.errors.size;
        if (errorCount === 0) return;
        
        const announcement = errorCount === 1 
            ? 'Hay 1 error en el formulario' 
            : `Hay ${errorCount} errores en el formulario`;
            
        this.announceToScreenReader(announcement);
    }
    
    /**
     * Anuncia mensaje a lectores de pantalla
     */
    announceToScreenReader(message) {
        const announcer = DOMUtils.createElement('div', {
            'aria-live': 'assertive',
            'aria-atomic': 'true',
            'class': 'sr-only'
        }, message);
        
        document.body.appendChild(announcer);
        setTimeout(() => announcer.remove(), 1000);
    }
    
    /**
     * Valida formato de email
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
}

/**
 * Gestor de indicador de progreso para formularios largos
 */
class ProgressIndicator {
    constructor(form) {
        this.form = form;
        this.questions = DOMUtils.$$('.pregunta-container', form);
        this.totalQuestions = this.questions.length;
        this.currentProgress = 0;
        this.init();
    }
    
    init() {
        if (this.totalQuestions <= 3) return; // No mostrar para formularios cortos
        
        this.createProgressBar();
        this.setupProgressTracking();
        this.updateProgress();
    }
    
    createProgressBar() {
        const progressHtml = `
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <div class="progress-text">
                    Pregunta <span id="current-question">1</span> de <span id="total-questions">${this.totalQuestions}</span>
                </div>
            </div>
        `;
        
        this.form.insertAdjacentHTML('afterbegin', progressHtml);
        this.progressFill = DOMUtils.$('.progress-fill', this.form);
        this.currentQuestionElement = DOMUtils.$('#current-question', this.form);
    }
    
    setupProgressTracking() {
        // Actualizar progreso cuando se responde una pregunta
        DOMUtils.$$('input, textarea', this.form).forEach(input => {
            input.addEventListener('change', () => this.updateProgress());
            input.addEventListener('input', () => this.updateProgress());
        });
    }
    
    updateProgress() {
        let answeredQuestions = 0;
        
        this.questions.forEach(question => {
            const inputs = DOMUtils.$$('input, textarea', question);
            const hasAnswer = inputs.some(input => {
                if (input.type === 'radio' || input.type === 'checkbox') {
                    return input.checked;
                }
                return input.value.trim() !== '';
            });
            
            if (hasAnswer) answeredQuestions++;
        });
        
        const progress = Math.round((answeredQuestions / this.totalQuestions) * 100);
        
        if (this.progressFill) {
            this.progressFill.style.width = `${progress}%`;
        }
        
        if (this.currentQuestionElement) {
            this.currentQuestionElement.textContent = Math.min(answeredQuestions + 1, this.totalQuestions);
        }
    }
}

/**
 * Gestor de envío de formularios con AJAX
 * Preserva anonimato según especificaciones del README
 */
class FormSubmitter {
    constructor(form, endpoint) {
        this.form = form;
        this.endpoint = endpoint;
        this.submitButton = DOMUtils.$('button[type="submit"]', form);
        this.setupSubmission();
    }
    
    setupSubmission() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }
    
    async handleSubmit(event) {
        event.preventDefault();
        
        if (!this.validateBeforeSubmit()) return;
        
        this.setLoadingState(true);
        
        try {
            const formData = this.collectFormData();
            const response = await this.submitData(formData);
            
            if (response.ok) {
                const result = await response.json();
                this.handleSuccess(result);
            } else {
                const error = await response.json();
                this.handleError(error.error || 'Error al enviar el formulario');
            }
        } catch (error) {
            console.error('Error de red:', error);
            this.handleError('Error de conexión. Inténtalo de nuevo.');
        } finally {
            this.setLoadingState(false);
        }
    }
      /**
     * Recolecta datos del formulario en formato anónimo
     */    collectFormData() {
        const formData = new FormData(this.form);
        const data = {
            formulario_id: null,
            respuestas: {}
        };
        
        // Procesar FormData y estructurar correctamente
        for (const [key, value] of formData.entries()) {
            if (key === 'formulario_id') {
                data.formulario_id = parseInt(value);
            } else if (key.startsWith('respuestas[')) {
                // Extraer ID de pregunta de respuestas[ID]
                const match = key.match(/respuestas\[(\d+)\]/);
                if (match) {
                    const preguntaId = match[1];
                    
                    // Manejar checkboxes múltiples
                    if (data.respuestas[preguntaId]) {
                        // Ya existe, convertir a array
                        if (!Array.isArray(data.respuestas[preguntaId])) {
                            data.respuestas[preguntaId] = [data.respuestas[preguntaId]];
                        }
                        data.respuestas[preguntaId].push(value);
                    } else {
                        data.respuestas[preguntaId] = value;
                    }
                }
            }
        }
        
        return data;
    }
      /**
     * Envía datos vía fetch (preservando anonimato)
     */    async submitData(data) {
        const response = await fetch(this.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // NO incluir headers que permitan tracking
            },
            body: JSON.stringify(data)
        });
        
        return response;
    }
    
    /**
     * Maneja respuesta exitosa
     */
    handleSuccess(result) {
        // Reemplazar formulario con mensaje de éxito
        const successMessage = `
            <div class="message message-success fade-in">
                <h2>¡Gracias por tu opinión!</h2>
                <p>Tu retroalimentación es completamente anónima y nos ayuda a mejorar continuamente nuestros servicios.</p>
                <p>La encuesta se ha enviado correctamente.</p>
            </div>
        `;
        
        this.form.innerHTML = successMessage;
        
        // Anunciar éxito a lectores de pantalla
        this.announceToScreenReader('Formulario enviado correctamente');
        
        // Scroll suave al mensaje
        this.form.scrollIntoView({ behavior: 'smooth' });
    }
    
    /**
     * Maneja errores de envío
     */
    handleError(errorMessage) {
        // Mostrar error en la parte superior del formulario
        this.clearPreviousErrors();
        
        const errorHtml = `
            <div class="message message-error" role="alert">
                <strong>Error:</strong> ${errorMessage}
            </div>
        `;
        
        this.form.insertAdjacentHTML('afterbegin', errorHtml);
        
        // Enfocar el mensaje de error
        const errorElement = DOMUtils.$('.message-error', this.form);
        DOMUtils.focusElement(errorElement);
        
        // Anunciar error a lectores de pantalla
        this.announceToScreenReader(`Error: ${errorMessage}`);
    }
    
    /**
     * Limpia errores previos
     */
    clearPreviousErrors() {
        const existingError = DOMUtils.$('.message-error', this.form);
        if (existingError) {
            existingError.remove();
        }
    }
    
    /**
     * Valida antes del envío
     */
    validateBeforeSubmit() {
        const validator = new FormValidator(this.form);
        return validator.validateForm({ preventDefault: () => {} });
    }
    
    /**
     * Maneja estado de carga del botón
     */
    setLoadingState(isLoading) {
        if (!this.submitButton) return;
        
        if (isLoading) {
            this.originalButtonText = this.submitButton.textContent;
            this.submitButton.textContent = 'Enviando...';
            this.submitButton.disabled = true;
            this.submitButton.classList.add('loading');
        } else {
            this.submitButton.textContent = this.originalButtonText;
            this.submitButton.disabled = false;
            this.submitButton.classList.remove('loading');
        }
    }
    
    /**
     * Anuncia mensaje a lectores de pantalla
     */
    announceToScreenReader(message) {
        const announcer = DOMUtils.createElement('div', {
            'aria-live': 'assertive',
            'aria-atomic': 'true',
            'class': 'sr-only'
        }, message);
        
        document.body.appendChild(announcer);
        setTimeout(() => announcer.remove(), 1000);
    }
}

/**
 * Gestor de preguntas dinámicas en formularios de administración
 */
class DynamicQuestionManager {
    constructor(container) {
        this.container = container;
        this.questionCount = 0;
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        const addButton = DOMUtils.$('.add-question-btn', this.container);
        if (addButton) {
            addButton.addEventListener('click', () => this.addQuestion());
        }
    }
    
    addQuestion() {
        this.questionCount++;
        const questionHtml = this.createQuestionHTML(this.questionCount);
        
        const questionsContainer = DOMUtils.$('.questions-container', this.container);
        questionsContainer.insertAdjacentHTML('beforeend', questionHtml);
        
        // Configurar evento de eliminación para la nueva pregunta
        const newQuestion = questionsContainer.lastElementChild;
        const removeButton = DOMUtils.$('.remove-question-btn', newQuestion);
        if (removeButton) {
            removeButton.addEventListener('click', () => this.removeQuestion(newQuestion));
        }
        
        // Enfocar el primer input de la nueva pregunta
        const firstInput = DOMUtils.$('input, textarea', newQuestion);
        if (firstInput) {
            DOMUtils.focusElement(firstInput);
        }
    }
    
    removeQuestion(questionElement) {
        // Confirmar eliminación
        if (confirm('¿Estás seguro de que quieres eliminar esta pregunta?')) {
            questionElement.remove();
            this.updateQuestionNumbers();
        }
    }
    
    createQuestionHTML(number) {
        return `
            <div class="question-item" data-question="${number}">
                <div class="form-group">
                    <label for="pregunta_texto_${number}">Texto de la pregunta ${number}</label>
                    <textarea id="pregunta_texto_${number}" name="pregunta_texto[]" required
                              placeholder="Escribe aquí la pregunta..." rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="pregunta_tipo_${number}">Tipo de respuesta</label>
                    <select id="pregunta_tipo_${number}" name="pregunta_tipo[]" required>
                        <option value="">Selecciona el tipo</option>
                        <option value="escala">Escala (1-5)</option>
                        <option value="texto">Texto libre</option>
                        <option value="seleccion">Selección simple</option>
                    </select>
                </div>
                
                <button type="button" class="btn btn-danger remove-question-btn">
                    Eliminar pregunta
                </button>
            </div>
        `;
    }
    
    updateQuestionNumbers() {
        const questions = DOMUtils.$$('.question-item', this.container);
        questions.forEach((question, index) => {
            const number = index + 1;
            question.dataset.question = number;
            
            // Actualizar labels
            const label = DOMUtils.$('label', question);
            if (label) {
                label.textContent = label.textContent.replace(/\d+/, number);
            }
        });
    }
}

/**
 * Inicialización cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar validación en todos los formularios
    DOMUtils.$$('form').forEach(form => {
        new FormValidator(form);
    });    // Inicialización de formularios de encuesta se maneja en llenar_formulario.php
    // para evitar duplicación y conflictos
    
    // Inicializar gestor de preguntas dinámicas en formularios de admin
    const adminFormContainer = DOMUtils.$('.admin-form-container');
    if (adminFormContainer) {
        new DynamicQuestionManager(adminFormContainer);
    }
    
    // Configurar confirmaciones de eliminación
    DOMUtils.$$('a[href*="eliminar"]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres eliminar este elemento?')) {
                e.preventDefault();
            }
        });
    });
    
    // Mejorar accesibilidad de tablas
    DOMUtils.$$('table').forEach(table => {
        table.setAttribute('role', 'table');
        const caption = DOMUtils.$('caption', table);
        if (!caption) {
            const newCaption = DOMUtils.createElement('caption', { class: 'sr-only' }, 
                'Tabla de datos del sistema');
            table.insertBefore(newCaption, table.firstChild);
        }
    });
    
    console.log('Sistema de Encuestas Anónimas inicializado correctamente');
});
