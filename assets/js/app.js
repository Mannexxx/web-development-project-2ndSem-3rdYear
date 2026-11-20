/* Small, dependency-free helpers used across pages. */
(function () {
  'use strict';

  var flashContainer = document.querySelector('.flashes');
  if (flashContainer) {
    var header = document.querySelector('.admin-header, .site-header, .m-appbar');
    if (header && header.getClientRects().length) {
      flashContainer.style.top = (header.getBoundingClientRect().bottom + 8) + 'px';
    }
  }

  // Automatically dismiss flash notices after they have been visible for five seconds.
  document.querySelectorAll('.flash').forEach(function (flash) {
    window.setTimeout(function () {
      flash.classList.add('flash-dismissing');
      window.setTimeout(function () {
        var container = flash.parentElement;
        flash.remove();
        if (container && container.classList.contains('flashes') && !container.querySelector('.flash')) {
          container.remove();
        }
      }, 250);
    }, 5000);
  });

  // Filters: submit the form as soon as a select changes.
  document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form && sel.form.submit(); });
  });

  // Mobile sort select mirrors the (hidden on mobile) desktop one.
  var mirror = document.querySelector('[data-mirror-sort]');
  if (mirror) {
    mirror.addEventListener('change', function () {
      var main = document.querySelector('select[name="sort"][data-autosubmit]');
      if (main) { main.value = mirror.value; main.form.submit(); }
    });
  }

  // Mobile "Filters" toggle.
  var toggle = document.querySelector('[data-toggle-filters]');
  var selects = document.getElementById('filter-selects');
  if (toggle && selects) {
    toggle.addEventListener('click', function () {
      var open = selects.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    // Keep it open when a filter is active so people can see why results changed.
    if (Array.prototype.some.call(selects.querySelectorAll('select'), function (s) { return s.value !== ''; })) {
      selects.classList.add('open');
    }
  }

  // Profile avatar menu in the site header.
  var profileMenuButton = document.querySelector('[data-profile-menu-button]');
  var profileMenu = profileMenuButton && document.getElementById(profileMenuButton.getAttribute('aria-controls'));
  if (profileMenuButton && profileMenu) {
    function setProfileMenuOpen(open) {
      profileMenu.hidden = !open;
      profileMenuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    profileMenuButton.addEventListener('click', function () {
      setProfileMenuOpen(profileMenuButton.getAttribute('aria-expanded') !== 'true');
    });
    document.addEventListener('click', function (event) {
      if (!profileMenu.contains(event.target) && !profileMenuButton.contains(event.target)) {
        setProfileMenuOpen(false);
      }
    });
    profileMenu.addEventListener('click', function (event) {
      if (event.target.closest('a, button')) {
        setProfileMenuOpen(false);
      }
    });
    profileMenu.addEventListener('keydown', function (event) {
      var items = Array.prototype.slice.call(profileMenu.querySelectorAll('[role="menuitem"]'));
      var currentIndex = items.indexOf(document.activeElement);
      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        var direction = event.key === 'ArrowDown' ? 1 : -1;
        var nextIndex = currentIndex < 0
          ? 0
          : (currentIndex + direction + items.length) % items.length;
        items[nextIndex].focus();
      } else if (event.key === 'Home' || event.key === 'End') {
        event.preventDefault();
        items[event.key === 'Home' ? 0 : items.length - 1].focus();
      }
    });
    profileMenu.addEventListener('focusout', function (event) {
      if (!profileMenu.contains(event.relatedTarget)) {
        setProfileMenuOpen(false);
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && profileMenuButton.getAttribute('aria-expanded') === 'true') {
        setProfileMenuOpen(false);
        profileMenuButton.focus();
      }
    });
  }

  // Product gallery thumbnails.
  document.querySelectorAll('[data-gallery]').forEach(function (g) {
    var main = g.querySelector('[data-gallery-main]');
    g.querySelectorAll('.thumb').forEach(function (t) {
      t.addEventListener('click', function () {
        if (main) { main.src = t.dataset.src; }
        g.querySelectorAll('.thumb').forEach(function (o) { o.classList.remove('active'); });
        t.classList.add('active');
      });
    });
  });

  // Share button (product page, mobile).
  document.querySelectorAll('[data-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      var data = { title: document.title, url: window.location.href };
      if (navigator.share) { navigator.share(data).catch(function () {}); return; }
      if (navigator.clipboard) {
        navigator.clipboard.writeText(data.url).then(function () { b.textContent = 'Link copied'; setTimeout(function () { b.textContent = 'Share'; }, 1800); });
      }
    });
  });

  // File input label shows the chosen file names.
  document.querySelectorAll('[data-file-input]').forEach(function (input) {
    var label = input.closest('label') && input.closest('label').querySelector('[data-file-label]');
    if (!label) return;
    var original = label.textContent;
    input.addEventListener('change', function () {
      var names = Array.prototype.map.call(input.files, function (f) { return f.name; });
      label.textContent = names.length ? names.join(', ') : original;
    });
  });

  // Confirm before destructive buttons.
  document.querySelectorAll('[data-confirm-click]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      if (!window.confirm(b.dataset.confirmClick)) { e.preventDefault(); }
    });
  });

  // Confirm dialogs for risky forms.
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.dataset.confirm)) { e.preventDefault(); }
    });
  });

  // Password visibility controls on auth pages.
  document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      var input = document.getElementById(button.dataset.passwordToggle);
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.textContent = show ? 'Hide' : 'Show';
      button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });

  // Switch between sign-in and registration without leaving the shared auth page.
  var authLinks = document.querySelectorAll('[data-auth-mode]');
  var authPanels = document.querySelectorAll('[data-auth-panel]');
  if (authLinks.length && authPanels.length) {
    function setAuthMode(mode, targetUrl) {
      authPanels.forEach(function (panel) {
        panel.hidden = panel.dataset.authPanel !== mode;
      });
      authLinks.forEach(function (link) {
        if (link.dataset.authMode === mode) {
          link.setAttribute('aria-current', 'page');
        } else {
          link.removeAttribute('aria-current');
        }
      });
      document.querySelectorAll('[data-auth-copy]').forEach(function (copy) {
        copy.textContent = copy.dataset[mode];
      });
      var authPage = document.querySelector('[data-auth-login-title]');
      if (authPage) {
        document.title = authPage.dataset['auth' + mode.charAt(0).toUpperCase() + mode.slice(1) + 'Title'];
      }
      if (targetUrl) {
        window.history.replaceState(null, '', targetUrl);
      }
    }

    authLinks.forEach(function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        setAuthMode(link.dataset.authMode, link.href);
      });
    });
  }

  // Show one purchase status section at a time.
  var purchaseTabs = Array.prototype.slice.call(document.querySelectorAll('[data-purchase-tab]'));
  var purchaseSections = document.querySelectorAll('.purchase-section');
  if (purchaseTabs.length && purchaseSections.length) {
    function purchaseStatusFromHash(hash) {
      return (hash || '').replace(/^#/, '').replace(/-/g, '_');
    }

    function activatePurchaseTab(status) {
      var selectedTab = purchaseTabs.find(function (tab) {
        return tab.getAttribute('aria-controls') === status;
      });
      if (!selectedTab) {
        selectedTab = purchaseTabs[0];
      }

      purchaseTabs.forEach(function (tab) {
        var selected = tab === selectedTab;
        tab.classList.toggle('active', selected);
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        tab.tabIndex = selected ? 0 : -1;
      });
      purchaseSections.forEach(function (section) {
        section.hidden = section.id !== selectedTab.getAttribute('aria-controls');
      });
    }

    purchaseTabs.forEach(function (tab, index) {
      tab.addEventListener('click', function (event) {
        event.preventDefault();
        window.history.pushState(null, '', tab.hash);
        activatePurchaseTab(purchaseStatusFromHash(tab.hash));
      });
      tab.addEventListener('keydown', function (event) {
        var nextIndex = index;
        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
          nextIndex = (index + 1) % purchaseTabs.length;
        } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
          nextIndex = (index - 1 + purchaseTabs.length) % purchaseTabs.length;
        } else if (event.key === 'Home') {
          nextIndex = 0;
        } else if (event.key === 'End') {
          nextIndex = purchaseTabs.length - 1;
        } else {
          return;
        }
        event.preventDefault();
        purchaseTabs[nextIndex].focus();
        purchaseTabs[nextIndex].click();
      });
    });

    window.addEventListener('popstate', function () {
      activatePurchaseTab(purchaseStatusFromHash(window.location.hash));
    });
    activatePurchaseTab(purchaseStatusFromHash(window.location.hash));
  }

  // Show one account section at a time.
  var accountTabs = Array.prototype.slice.call(document.querySelectorAll('[data-account-tab]'));
  var accountSections = document.querySelectorAll('.account-panel');
  var accountTabList = document.querySelector('[data-account-default]');
  if (accountTabs.length && accountSections.length && accountTabList) {
    function activateAccountTab(id) {
      var selectedTab = accountTabs.find(function (tab) {
        return tab.getAttribute('aria-controls') === id;
      }) || accountTabs.find(function (tab) {
        return tab.getAttribute('aria-controls') === accountTabList.dataset.accountDefault;
      }) || accountTabs[0];

      accountTabs.forEach(function (tab) {
        var selected = tab === selectedTab;
        tab.classList.toggle('active', selected);
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        tab.tabIndex = selected ? 0 : -1;
      });
      accountSections.forEach(function (section) {
        section.hidden = section.id !== selectedTab.getAttribute('aria-controls');
      });
    }

    accountTabs.forEach(function (tab, index) {
      tab.addEventListener('click', function (event) {
        event.preventDefault();
        window.history.pushState(null, '', tab.hash);
        activateAccountTab(tab.hash.slice(1));
      });
      tab.addEventListener('keydown', function (event) {
        var nextIndex = index;
        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
          nextIndex = (index + 1) % accountTabs.length;
        } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
          nextIndex = (index - 1 + accountTabs.length) % accountTabs.length;
        } else if (event.key === 'Home') {
          nextIndex = 0;
        } else if (event.key === 'End') {
          nextIndex = accountTabs.length - 1;
        } else {
          return;
        }
        event.preventDefault();
        accountTabs[nextIndex].focus();
        accountTabs[nextIndex].click();
      });
    });

    window.addEventListener('popstate', function () {
      activateAccountTab(window.location.hash.slice(1));
    });
    activateAccountTab(window.location.hash.slice(1) || accountTabList.dataset.accountDefault);
  }
})();
