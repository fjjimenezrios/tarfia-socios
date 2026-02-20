/* Tarfia Socios — JS para nav móvil, UX y sistema de toasts */

// ===== TOAST SYSTEM =====
window.TarfiaToast = (function() {
  var container = null;
  var toastQueue = [];
  
  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.className = 'tarfia-toast-container';
      container.setAttribute('aria-live', 'polite');
      document.body.appendChild(container);
    }
    return container;
  }
  
  function getIcon(type) {
    var icons = {
      success: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
      error: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
      warning: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 5V8M8 11H8.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
      info: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="2"/><path d="M8 7V11M8 5H8.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
      loading: '<div class="tarfia-toast-spinner"></div>'
    };
    return icons[type] || icons.info;
  }
  
  function getTitle(type) {
    var titles = {
      success: 'Correcto',
      error: 'Error',
      warning: 'Atención',
      info: 'Información',
      loading: 'Cargando'
    };
    return titles[type] || '';
  }
  
  function createToast(options) {
    var type = options.type || 'info';
    var message = options.message || '';
    var title = options.title || getTitle(type);
    var duration = options.duration !== undefined ? options.duration : (type === 'loading' ? 0 : 4000);
    var closable = options.closable !== false && type !== 'loading';
    var showProgress = options.progress !== false && duration > 0;
    
    var toast = document.createElement('div');
    toast.className = 'tarfia-toast tarfia-toast-' + type;
    toast.setAttribute('role', 'alert');
    
    var html = '';
    html += '<div class="tarfia-toast-icon">' + getIcon(type) + '</div>';
    html += '<div class="tarfia-toast-content">';
    if (title) {
      html += '<div class="tarfia-toast-title">' + title + '</div>';
    }
    html += '<div class="tarfia-toast-message">' + message + '</div>';
    html += '</div>';
    
    if (closable) {
      html += '<button class="tarfia-toast-close" aria-label="Cerrar">';
      html += '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M11 3L3 11M3 3L11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
      html += '</button>';
    }
    
    if (showProgress) {
      html += '<div class="tarfia-toast-progress" style="width: 100%"></div>';
    }
    
    toast.innerHTML = html;
    
    var closeBtn = toast.querySelector('.tarfia-toast-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function() {
        hideToast(toast);
      });
    }
    
    return { element: toast, duration: duration, showProgress: showProgress };
  }
  
  function showToast(toastData) {
    var container = getContainer();
    container.appendChild(toastData.element);
    
    requestAnimationFrame(function() {
      requestAnimationFrame(function() {
        toastData.element.classList.add('show');
      });
    });
    
    if (toastData.duration > 0) {
      if (toastData.showProgress) {
        var progress = toastData.element.querySelector('.tarfia-toast-progress');
        if (progress) {
          progress.style.transition = 'width ' + toastData.duration + 'ms linear';
          requestAnimationFrame(function() {
            progress.style.width = '0%';
          });
        }
      }
      
      toastData.timeout = setTimeout(function() {
        hideToast(toastData.element);
      }, toastData.duration);
    }
    
    return toastData.element;
  }
  
  function hideToast(toast) {
    if (!toast || toast.classList.contains('hiding')) return;
    
    toast.classList.remove('show');
    toast.classList.add('hiding');
    
    setTimeout(function() {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 400);
  }
  
  // Public API
  return {
    show: function(options) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      var toastData = createToast(options);
      return showToast(toastData);
    },
    
    success: function(message, title) {
      return this.show({ type: 'success', message: message, title: title });
    },
    
    error: function(message, title) {
      return this.show({ type: 'error', message: message, title: title, duration: 6000 });
    },
    
    warning: function(message, title) {
      return this.show({ type: 'warning', message: message, title: title });
    },
    
    info: function(message, title) {
      return this.show({ type: 'info', message: message, title: title });
    },
    
    loading: function(message) {
      return this.show({ type: 'loading', message: message || 'Por favor espera...', duration: 0 });
    },
    
    hide: function(toast) {
      hideToast(toast);
    },
    
    confirm: function(options) {
      return new Promise(function(resolve) {
        var container = getContainer();
        
        var toast = document.createElement('div');
        toast.className = 'tarfia-toast tarfia-toast-confirm tarfia-toast-warning';
        
        var message = options.message || '¿Estás seguro?';
        var title = options.title || 'Confirmar acción';
        var confirmText = options.confirmText || 'Confirmar';
        var cancelText = options.cancelText || 'Cancelar';
        var confirmClass = options.danger !== false ? 'tarfia-toast-btn-confirm' : 'tarfia-toast-btn-primary';
        
        var html = '<div class="tarfia-toast-header">';
        html += '<div class="tarfia-toast-icon">' + getIcon('warning') + '</div>';
        html += '<div class="tarfia-toast-content">';
        html += '<div class="tarfia-toast-title">' + title + '</div>';
        html += '<div class="tarfia-toast-message">' + message + '</div>';
        html += '</div></div>';
        html += '<div class="tarfia-toast-actions">';
        html += '<button class="tarfia-toast-btn tarfia-toast-btn-cancel">' + cancelText + '</button>';
        html += '<button class="tarfia-toast-btn ' + confirmClass + '">' + confirmText + '</button>';
        html += '</div>';
        
        toast.innerHTML = html;
        container.appendChild(toast);
        
        requestAnimationFrame(function() {
          requestAnimationFrame(function() {
            toast.classList.add('show');
          });
        });
        
        var btnCancel = toast.querySelector('.tarfia-toast-btn-cancel');
        var btnConfirm = toast.querySelector('.' + confirmClass.split(' ')[0]);
        
        function close(result) {
          hideToast(toast);
          resolve(result);
        }
        
        btnCancel.addEventListener('click', function() { close(false); });
        btnConfirm.addEventListener('click', function() { close(true); });
      });
    },
    
    // Actualizar un toast existente (útil para loading -> success/error)
    update: function(toast, options) {
      if (!toast) return;
      
      var type = options.type || 'info';
      toast.className = 'tarfia-toast tarfia-toast-' + type + ' show';
      
      var icon = toast.querySelector('.tarfia-toast-icon');
      var title = toast.querySelector('.tarfia-toast-title');
      var message = toast.querySelector('.tarfia-toast-message');
      
      if (icon) icon.innerHTML = getIcon(type);
      if (title) title.textContent = options.title || getTitle(type);
      if (message) message.textContent = options.message || '';
      
      // Añadir botón cerrar si no existe
      if (!toast.querySelector('.tarfia-toast-close') && type !== 'loading') {
        var closeBtn = document.createElement('button');
        closeBtn.className = 'tarfia-toast-close';
        closeBtn.setAttribute('aria-label', 'Cerrar');
        closeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M11 3L3 11M3 3L11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        closeBtn.addEventListener('click', function() { hideToast(toast); });
        toast.appendChild(closeBtn);
      }
      
      // Auto-cerrar después de un tiempo
      var duration = options.duration !== undefined ? options.duration : 4000;
      if (duration > 0) {
        setTimeout(function() { hideToast(toast); }, duration);
      }
    }
  };
})();

// Alias cortos
window.toast = window.TarfiaToast;

// ===== NAV MÓVIL =====
(function () {
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      links.classList.toggle('show');
    });
    links.addEventListener('click', function () {
      links.classList.remove('show');
    });
  }
})();

// ===== AUTO-SHOW TOASTS FROM PHP =====
document.addEventListener('DOMContentLoaded', function() {
  // Buscar alertas de PHP y convertirlas en toasts
  var alerts = document.querySelectorAll('.tarfia-alert[data-toast]');
  alerts.forEach(function(alert) {
    var type = alert.dataset.toast || 'info';
    var message = alert.textContent.trim();
    
    TarfiaToast.show({ type: type, message: message });
    alert.style.display = 'none';
  });
});
