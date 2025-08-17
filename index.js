// FIXED: Enhanced Menu Toggle System
class DropdownMenu {
  constructor() {
    this.menuButton = document.getElementById('menuButton');
    this.menuContainer = document.getElementById('menuContainer');
    this.dropdownMenu = document.getElementById('dropdownMenu');
    this.menuOverlay = document.getElementById('menuOverlay');
    this.isOpen = false;
    this.isMobile = window.innerWidth <= 767.98;
    
    this.init();
  }
  
  init() {
    // Remove any existing event listeners
    this.cleanup();
    
    // Add click event for menu button
    this.menuButton.addEventListener('click', this.toggleMenu.bind(this));
    
    // Add overlay click for mobile
    this.menuOverlay.addEventListener('click', this.closeMenu.bind(this));
    
    // Add document click to close menu when clicking outside
    document.addEventListener('click', this.handleDocumentClick.bind(this));
    
    // Add keyboard support
    this.menuButton.addEventListener('keydown', this.handleKeydown.bind(this));
    this.dropdownMenu.addEventListener('keydown', this.handleMenuKeydown.bind(this));
    
    // Add resize listener to detect mobile/desktop switches
    window.addEventListener('resize', this.handleResize.bind(this));
    
    // Add menu item clicks
    this.dropdownMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        if (this.isMobile) {
          this.closeMenu();
        }
      });
    });
  }
  
  cleanup() {
    // Remove existing listeners (if any)
    this.menuButton?.removeEventListener('click', this.toggleMenu);
    this.menuOverlay?.removeEventListener('click', this.closeMenu);
    document.removeEventListener('click', this.handleDocumentClick);
  }
  
  toggleMenu(event) {
    event.stopPropagation();
    event.preventDefault();
    
    if (this.isOpen) {
      this.closeMenu();
    } else {
      this.openMenu();
    }
  }
  
  openMenu() {
    this.isOpen = true;
    this.menuContainer.classList.add('active');
    this.menuButton.setAttribute('aria-expanded', 'true');
    
    // Add active state styles
    this.dropdownMenu.style.opacity = '1';
    this.dropdownMenu.style.visibility = 'visible';
    this.dropdownMenu.style.transform = 'translateY(0) scale(1)';
    
    if (this.isMobile) {
      this.menuOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    
    // Focus first menu item for keyboard navigation
    const firstMenuItem = this.dropdownMenu.querySelector('a');
    if (firstMenuItem) {
      setTimeout(() => firstMenuItem.focus(), 100);
    }
    
    // Add haptic feedback if available
    if (navigator.vibrate) {
      navigator.vibrate(50);
    }
  }
  
  closeMenu() {
    this.isOpen = false;
    this.menuContainer.classList.remove('active');
    this.menuButton.setAttribute('aria-expanded', 'false');
    
    // Remove active state styles
    this.dropdownMenu.style.opacity = '0';
    this.dropdownMenu.style.visibility = 'hidden';
    this.dropdownMenu.style.transform = 'translateY(-15px) scale(0.95)';
    
    if (this.isMobile) {
      this.menuOverlay.classList.remove('active');
      document.body.style.overflow = '';
    }
  }
  
  handleDocumentClick(event) {
    // Close menu if clicking outside
    if (!this.menuContainer.contains(event.target)) {
      this.closeMenu();
    }
  }
  
  handleKeydown(event) {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      this.toggleMenu(event);
    } else if (event.key === 'ArrowDown') {
      event.preventDefault();
      this.openMenu();
    }
  }
  
  handleMenuKeydown(event) {
    const menuItems = Array.from(this.dropdownMenu.querySelectorAll('a'));
    const currentIndex = menuItems.indexOf(document.activeElement);
    
    switch (event.key) {
      case 'Escape':
        event.preventDefault();
        this.closeMenu();
        this.menuButton.focus();
        break;
      case 'ArrowUp':
        event.preventDefault();
        const prevIndex = currentIndex > 0 ? currentIndex - 1 : menuItems.length - 1;
        menuItems[prevIndex].focus();
        break;
      case 'ArrowDown':
        event.preventDefault();
        const nextIndex = currentIndex < menuItems.length - 1 ? currentIndex + 1 : 0;
        menuItems[nextIndex].focus();
        break;
      case 'Home':
        event.preventDefault();
        menuItems[0].focus();
        break;
      case 'End':
        event.preventDefault();
        menuItems[menuItems.length - 1].focus();
        break;
    }
  }
  
  handleResize() {
    const wasMobile = this.isMobile;
    this.isMobile = window.innerWidth <= 767.98;
    
    // If switching from mobile to desktop or vice versa, close menu
    if (wasMobile !== this.isMobile && this.isOpen) {
      this.closeMenu();
    }
  }
}

// Enhanced particle system for animated background
class ParticleSystem {
  constructor() {
    this.particleContainer = document.querySelector('.bg-particles');
    this.particles = [];
    this.maxParticles = 15;
    this.isActive = true;
    
    if (this.particleContainer) {
      this.init();
    }
  }
  
  init() {
    // Clear existing particles
    this.particleContainer.innerHTML = '';
    
    // Create initial particles
    for (let i = 0; i < this.maxParticles; i++) {
      setTimeout(() => {
        this.createParticle();
      }, i * 300);
    }
    
    // Continue creating particles
    this.startParticleGeneration();
    
    // Handle visibility change to pause/resume
    document.addEventListener('visibilitychange', () => {
      this.isActive = !document.hidden;
      if (this.isActive) {
        this.startParticleGeneration();
      }
    });
  }
  
  createParticle() {
    if (!this.isActive || !this.particleContainer) return;
    
    const particle = document.createElement('div');
    particle.className = 'particle';
    
    // Random positioning and animation properties
    const leftPosition = Math.random() * 100;
    const animationDuration = Math.random() * 15 + 10; // 10-25 seconds
    const size = Math.random() * 4 + 2; // 2-6px
    const opacity = Math.random() * 0.6 + 0.2; // 0.2-0.8
    
    particle.style.left = leftPosition + '%';
    particle.style.width = size + 'px';
    particle.style.height = size + 'px';
    particle.style.animationDuration = animationDuration + 's';
    particle.style.opacity = opacity;
    particle.style.animationDelay = '0s';
    
    // Add some variety to particle colors
    const colorVariations = [
      'rgba(255, 255, 255, 0.3)',
      'rgba(255, 255, 255, 0.4)',
      'rgba(220, 252, 231, 0.3)',
      'rgba(187, 247, 208, 0.3)',
      'rgba(134, 239, 172, 0.2)'
    ];
    
    particle.style.background = colorVariations[Math.floor(Math.random() * colorVariations.length)];
    
    this.particleContainer.appendChild(particle);
    this.particles.push(particle);
    
    // Remove particle after animation completes
    setTimeout(() => {
      if (particle.parentNode) {
        particle.remove();
      }
      const index = this.particles.indexOf(particle);
      if (index > -1) {
        this.particles.splice(index, 1);
      }
    }, animationDuration * 1000);
  }
  
  startParticleGeneration() {
    if (!this.isActive) return;
    
    // Create new particles periodically
    const createInterval = setInterval(() => {
      if (!this.isActive) {
        clearInterval(createInterval);
        return;
      }
      
      if (this.particles.length < this.maxParticles) {
        this.createParticle();
      }
    }, 2000); // Create a new particle every 2 seconds
  }
}

// Mobile Responsive JavaScript Functions
function createMobileCards() {
  const table = document.querySelector('.attendance-table');
  const mobileContainer = document.querySelector('.mobile-attendance-cards');
  
  if (!table || !mobileContainer) return;

  // Clear existing mobile cards
  mobileContainer.innerHTML = '';

  // Get table data
  const rows = table.querySelectorAll('tbody tr');
  
  if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('td[colspan]'))) {
    // Handle empty state
    mobileContainer.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-info-circle"></i>
        <p>No attendance records found.</p>
      </div>
    `;
    return;
  }

  // Create cards from table data
  rows.forEach((row, index) => {
    const cells = row.querySelectorAll('td');
    if (cells.length >= 4 && !cells[0].hasAttribute('colspan')) {
      const card = document.createElement('div');
      card.className = 'attendance-card';
      card.style.animationDelay = `${index * 0.1}s`;
      
      card.innerHTML = `
        <div class="attendance-card-row">
          <span class="attendance-card-label">
            <i class="fas fa-hashtag"></i> Serial No
          </span>
          <span class="attendance-card-value">${cells[0].textContent.trim()}</span>
        </div>
        <div class="attendance-card-row">
          <span class="attendance-card-label">
            <i class="fas fa-id-card"></i> Student ID
          </span>
          <span class="attendance-card-value">${cells[1].textContent.trim()}</span>
        </div>
        <div class="attendance-card-row">
          <span class="attendance-card-label">
            <i class="fas fa-user"></i> Student Name
          </span>
          <span class="attendance-card-value">${cells[2].textContent.trim()}</span>
        </div>
        <div class="attendance-card-row">
          <span class="attendance-card-label">
            <i class="fas fa-calendar-day"></i> Date
          </span>
          <span class="attendance-card-value">${cells[3].textContent.trim()}</span>
        </div>
      `;
      
      mobileContainer.appendChild(card);
    }
  });
}

// Function to handle responsive layout changes
function handleResponsiveLayout() {
  const isMobile = window.innerWidth <= 767.98;
  
  if (isMobile) {
    createMobileCards();
  }
}

// Function to optimize touch interactions
function optimizeTouchInteractions() {
  // Add touch-friendly scrolling for dropdowns on mobile
  const dropdown = document.querySelector('.dropdown-menu');
  if (dropdown && 'ontouchstart' in window) {
    dropdown.style.overscrollBehavior = 'contain';
    dropdown.style.webkitOverflowScrolling = 'touch';
  }

  // Prevent zoom on input focus for iOS
  const inputs = document.querySelectorAll('input[type="text"], input[type="date"]');
  inputs.forEach(input => {
    input.addEventListener('focus', function() {
      if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
        input.style.fontSize = '16px';
      }
    });
  });

  // Add haptic feedback for supported devices
  if ('vibrate' in navigator) {
    const buttons = document.querySelectorAll('.btn, button[type="submit"]');
    buttons.forEach(button => {
      button.addEventListener('click', function() {
        navigator.vibrate(50); // Short vibration
      });
    });
  }
}

// Function to setup form validation with mobile-friendly alerts
function setupMobileFormValidation() {
  const searchForm = document.querySelector('form[method="post"]');
  if (!searchForm) return;

  searchForm.addEventListener('submit', function(e) {
    const studentId = document.getElementById('search_student_id');
    const searchDate = document.getElementById('search_date');
    
    if (studentId && searchDate) {
      const studentIdValue = studentId.value.trim();
      const searchDateValue = searchDate.value.trim();
      
      if (!studentIdValue && !searchDateValue) {
        e.preventDefault();
        
        // Mobile-friendly alert
        if (window.innerWidth <= 767.98) {
          // Simple alert for better mobile UX
          alert('Please enter either a Student ID or select a date to search.');
          studentId.focus();
        } else {
          // SweetAlert for desktop
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: 'Search Criteria Required',
              text: 'Please enter either a Student ID or select a date to search.',
              confirmButtonColor: '#22c55e',
              background: 'rgba(28, 204, 16, 0.95)',
              color: '#ffffff'
            });
          }
        }
      }
    }
  });
}

// Function to add pull-to-refresh functionality
function addPullToRefresh() {
  let startY = 0;
  let currentY = 0;
  let isRefreshing = false;
  
  const refreshThreshold = 80;
  const body = document.body;
  
  // Create refresh indicator
  let refreshIndicator = document.querySelector('.refresh-indicator');
  if (!refreshIndicator) {
    refreshIndicator = document.createElement('div');
    refreshIndicator.className = 'refresh-indicator';
    refreshIndicator.innerHTML = '<i class="fas fa-sync-alt"></i> Release to refresh';
    body.appendChild(refreshIndicator);
  }
  
  // Touch events for pull-to-refresh
  body.addEventListener('touchstart', function(e) {
    if (window.pageYOffset === 0) {
      startY = e.touches[0].clientY;
    }
  }, { passive: true });
  
  body.addEventListener('touchmove', function(e) {
    if (window.pageYOffset === 0 && !isRefreshing) {
      currentY = e.touches[0].clientY;
      const pullDistance = currentY - startY;
      
      if (pullDistance > 0 && pullDistance < refreshThreshold * 2) {
        const progress = Math.min(pullDistance / refreshThreshold, 1);
        refreshIndicator.style.top = `${-60 + (60 * progress)}px`;
        
        if (pullDistance >= refreshThreshold) {
          refreshIndicator.innerHTML = '<i class="fas fa-sync-alt"></i> Release to refresh';
          refreshIndicator.style.background = 'rgba(34, 197, 94, 0.9)';
        } else {
          refreshIndicator.innerHTML = '<i class="fas fa-arrow-down"></i> Pull to refresh';
          refreshIndicator.style.background = 'rgba(255, 255, 255, 0.2)';
        }
      }
    }
  }, { passive: true });
  
  body.addEventListener('touchend', function() {
    if (window.pageYOffset === 0 && !isRefreshing) {
      const pullDistance = currentY - startY;
      
      if (pullDistance >= refreshThreshold) {
        isRefreshing = true;
        refreshIndicator.style.top = '0px';
        refreshIndicator.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
        refreshIndicator.style.background = 'rgba(34, 197, 94, 0.9)';
        
        // Simulate refresh (reload page)
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        refreshIndicator.style.top = '-60px';
      }
    }
    
    startY = 0;
    currentY = 0;
  }, { passive: true });
}

// Access denied function with modern styling
function showAccessDenied() {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      icon: 'error',
      title: 'Access Denied',
      text: 'Admins Only!!',
      confirmButtonColor: '#22c55e',
      confirmButtonText: 'OK',
      background: 'rgba(255, 255, 255, 1)',
      customClass: {
        popup: 'swal-custom-popup'
      }
    });
  } else {
    alert('Access Denied - Admins Only!!');
  }
}

// Add ripple effect to buttons
function createRipple(event) {
  const button = event.currentTarget;
  const circle = document.createElement('span');
  const diameter = Math.max(button.clientWidth, button.clientHeight);
  const radius = diameter / 2;

  circle.style.width = circle.style.height = `${diameter}px`;
  circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
  circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
  circle.classList.add('ripple');

  const ripple = button.getElementsByClassName('ripple')[0];
  if (ripple) {
    ripple.remove();
  }

  button.appendChild(circle);
}

// Performance optimization: debounce function
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Enhanced particle creation for existing particles
function enhanceExistingParticles() {
  const existingParticles = document.querySelectorAll('.bg-particles .particle');
  existingParticles.forEach((particle, index) => {
    // Add random animation delays to existing particles
    const delay = Math.random() * 5;
    particle.style.animationDelay = delay + 's';
    
    // Add slight variations in size and opacity
    const size = Math.random() * 2 + 3; // 3-5px
    const opacity = Math.random() * 0.3 + 0.2; // 0.2-0.5
    
    particle.style.width = size + 'px';
    particle.style.height = size + 'px';
    particle.style.opacity = opacity;
  });
}

// Main initialization function
function initializeMobileResponsive() {
  // Initialize the dropdown menu system
  new DropdownMenu();
  
  // Initialize particle system
  new ParticleSystem();
  
  // Enhance existing particles if they exist
  enhanceExistingParticles();
  
  // Initial setup
  handleResponsiveLayout();
  optimizeTouchInteractions();
  setupMobileFormValidation();
  
  // Add pull-to-refresh only on mobile devices
  if ('ontouchstart' in window && window.innerWidth <= 767.98) {
    addPullToRefresh();
  }
  
  // Handle resize events with debouncing
  const optimizedResize = debounce(() => {
    handleResponsiveLayout();
  }, 250);
  window.addEventListener('resize', optimizedResize);
  
  // Handle orientation change
  window.addEventListener('orientationchange', function() {
    setTimeout(function() {
      handleResponsiveLayout();
      // Force layout recalculation
      document.body.style.display = 'none';
      document.body.offsetHeight; // Trigger reflow
      document.body.style.display = '';
    }, 300);
  });

  // Add ripple effect to buttons
  const buttons = document.querySelectorAll('.btn, button[type="submit"], .menu-button');
  buttons.forEach(button => {
    button.addEventListener('click', createRipple);
  });

  // Initialize table-to-cards conversion for mobile
  setTimeout(() => {
    handleResponsiveLayout();
  }, 100);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initializeMobileResponsive);

// Also initialize if the script loads after DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeMobileResponsive);
} else {
  initializeMobileResponsive();
}