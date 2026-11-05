/**
 * PT MBG — Home Page JS
 * Hero slider + nav + reveal animations
 */

(function () {
  'use strict';

  /* ---------- Hero Slider ---------- */
  const slides = document.querySelectorAll('.hero-slide');
  const dotsWrap = document.querySelector('.hero-dots');
  const prevBtn = document.querySelector('.hero-prev');
  const nextBtn = document.querySelector('.hero-next');
  const serviceDialog = document.querySelector('.service-contact-dialog');
  const serviceForm = serviceDialog?.querySelector('.service-contact-form');
  const selectedService = serviceDialog?.querySelector('[data-selected-service]');
  const chatForm = document.querySelector('[data-chat-form]');
  const chatMessages = document.querySelector('[data-chat-messages]');
  const chatInput = chatForm?.elements.namedItem('question');
  const chatWhatsapp = document.querySelector('.about-chat-whatsapp');
  const chatPanel = document.querySelector('#aboutChatPanel');
  const chatToggle = document.querySelector('.chatbot-toggle');
  const chatClose = document.querySelector('[data-chat-close]');
  let current = 0;
  let timer = null;
  const INTERVAL = 6000;

  function setChatOpen(open) {
    if (!chatPanel || !chatToggle) return;
    chatPanel.hidden = !open;
    chatToggle.setAttribute('aria-expanded', String(open));
    chatToggle.setAttribute('aria-label', open ? 'Tutup chatbot' : 'Buka chatbot');
    if (open) chatInput?.focus();
    else chatToggle.focus();
  }

  chatToggle?.addEventListener('click', () => {
    setChatOpen(chatPanel?.hidden ?? true);
  });
  chatClose?.addEventListener('click', () => setChatOpen(false));

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && chatPanel && !chatPanel.hidden) setChatOpen(false);
  });

  function answerChatQuestion(question) {
    const normalizedQuestion = question.toLocaleLowerCase('id-ID');

    if (/layanan|jasa|service/.test(normalizedQuestion)) {
      return 'PT MBG menyediakan pengembangan software, konsultasi IT, serta pendidikan dan pelatihan digital. Pilih tombol Hubungi pada kartu layanan untuk berkonsultasi.';
    }
    if (/harga|biaya|tarif|price/.test(normalizedQuestion)) {
      return 'Biaya bergantung pada ruang lingkup dan kebutuhan Anda. Ceritakan kebutuhan melalui WhatsApp agar tim kami dapat membantu menyiapkan estimasi.';
    }
    if (/kontak|hubungi|whatsapp|wa\b|telepon/.test(normalizedQuestion)) {
      return 'Anda dapat menghubungi tim PT MBG melalui WhatsApp. Gunakan tautan “Lanjutkan percakapan di WhatsApp” di bawah chat ini.';
    }
    if (/produk|katalog|windows|kaspersky|google cloud|office|microsoft 365/.test(normalizedQuestion)) {
      return 'Katalog produk tersedia di menu Produk. Untuk ketersediaan atau rekomendasi produk, silakan hubungi tim kami melalui WhatsApp.';
    }
    if (/training|pelatihan|kursus|bootcamp|pendidikan/.test(normalizedQuestion)) {
      return 'PT MBG menyediakan pelatihan IT, bootcamp coding dan data, serta kelas cloud dan cybersecurity. Hubungi kami untuk membahas program yang sesuai.';
    }
    if (/software|aplikasi|web|mobile|sistem|erp|crm/.test(normalizedQuestion)) {
      return 'Kami membantu membangun aplikasi web dan mobile, sistem custom, ERP/CRM, API, serta solusi cloud. Klik Hubungi pada kartu IT Software untuk berdiskusi.';
    }
    if (/konsultasi|consultant|audit|migrasi|cloud|arsitektur|strategi/.test(normalizedQuestion)) {
      return 'Tim konsultan dapat membantu roadmap digital, audit IT, strategi migrasi cloud, arsitektur sistem, dan tata kelola TI. Gunakan tombol Hubungi pada kartu IT Consultant.';
    }

    return 'Saya belum menemukan jawaban untuk pertanyaan itu. Tim kami bisa membantu langsung melalui WhatsApp; tautan di bawah akan menyertakan pertanyaan Anda.';
  }

  function addChatMessage(text, kind) {
    if (!chatMessages) return;
    const message = document.createElement('p');
    message.className = 'about-chat-message is-' + kind;
    message.textContent = text;
    chatMessages.append(message);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function submitChatQuestion(question) {
    const normalizedQuestion = question.trim();
    if (!normalizedQuestion) return;

    addChatMessage(normalizedQuestion, 'user');
    addChatMessage(answerChatQuestion(normalizedQuestion), 'bot');
    if (chatWhatsapp) {
      const whatsappMessage = 'Halo PT MBG, saya ingin bertanya: ' + normalizedQuestion;
      chatWhatsapp.href = 'https://wa.me/6281313616756?text=' + encodeURIComponent(whatsappMessage);
    }
  }

  chatForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!chatInput) return;
    submitChatQuestion(chatInput.value);
    chatForm.reset();
    chatInput.focus();
  });

  document.querySelectorAll('[data-chat-question]').forEach((button) => {
    button.addEventListener('click', () => {
      submitChatQuestion(button.dataset.chatQuestion || '');
      chatInput?.focus();
    });
  });

  document.querySelectorAll('[data-service-contact]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!serviceDialog || !selectedService || typeof serviceDialog.showModal !== 'function') return;
      selectedService.textContent = button.dataset.serviceContact;
      serviceDialog.showModal();
      serviceForm?.elements.namedItem('name')?.focus();
    });
  });

  serviceDialog?.querySelector('.service-contact-close')?.addEventListener('click', () => {
    serviceDialog.close();
  });

  serviceDialog?.addEventListener('click', (event) => {
    if (event.target === serviceDialog) serviceDialog.close();
  });

  serviceForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!selectedService) return;

    const formData = new FormData(serviceForm);
    const name = String(formData.get('name') || '').trim();
    const phone = String(formData.get('phone') || '').trim();
    const message = String(formData.get('message') || '').trim();
    const whatsappMessage = [
      'Halo PT MBG, saya ingin menghubungi terkait layanan ' + selectedService.textContent + '.',
      'Nama: ' + name,
      phone ? 'Nomor WhatsApp: ' + phone : '',
      'Kebutuhan: ' + message,
    ].filter(Boolean).join('\n');
    const whatsappUrl = 'https://wa.me/6281313616756?text=' + encodeURIComponent(whatsappMessage);
    const whatsappWindow = window.open(whatsappUrl, '_blank');

    if (whatsappWindow) {
      whatsappWindow.opener = null;
      serviceDialog.close();
    } else {
      window.location.assign(whatsappUrl);
    }
  });

  if (!slides.length) return;

  // Build dots
  slides.forEach((_, i) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('aria-label', 'Slide ' + (i + 1));
    if (i === 0) btn.classList.add('active');
    btn.addEventListener('click', () => goTo(i));
    dotsWrap.appendChild(btn);
  });

  const dots = dotsWrap.querySelectorAll('button');

  function goTo(index) {
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    // restart progress bar
    dots[current].style.animation = 'none';

    current = (index + slides.length) % slides.length;

    slides[current].classList.add('active');
    dots[current].classList.add('active');
    // force reflow for progress animation
    void dots[current].offsetWidth;

    resetTimer();
  }

  function next() { goTo(current + 1); }
  function prev() { goTo(current - 1); }

  function resetTimer() {
    clearInterval(timer);
    timer = setInterval(next, INTERVAL);
  }

  if (nextBtn) nextBtn.addEventListener('click', next);
  if (prevBtn) prevBtn.addEventListener('click', prev);

  // Keyboard
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
  });

  // Touch swipe
  let touchX = 0;
  const hero = document.querySelector('.hero');
  if (hero) {
    hero.addEventListener('touchstart', (e) => {
      touchX = e.changedTouches[0].screenX;
    }, { passive: true });
    hero.addEventListener('touchend', (e) => {
      const dx = e.changedTouches[0].screenX - touchX;
      if (Math.abs(dx) > 50) (dx < 0 ? next : prev)();
    }, { passive: true });
  }

  // Pause on hover
  if (hero) {
    hero.addEventListener('mouseenter', () => clearInterval(timer));
    hero.addEventListener('mouseleave', resetTimer);
  }

  resetTimer();

  /* ---------- Sticky nav scroll state ---------- */
  const nav = document.querySelector('.nav');
  const navWrap = document.querySelector('.nav-wrap');

  function onScroll() {
    if (window.scrollY > 40) {
      nav.classList.add('scrolled');
    } else {
      nav.classList.remove('scrolled');
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Mobile menu ---------- */
  const toggle = document.querySelector('.nav-toggle');
  const links = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', links.classList.contains('open'));
    });
    links.querySelectorAll('a').forEach((a) => {
      a.addEventListener('click', () => links.classList.remove('open'));
    });
  }

  /* ---------- Active nav link on scroll ---------- */
  const sections = document.querySelectorAll('section[id]');
  const navAnchors = document.querySelectorAll('.nav-links a[href^="#"]');

  function updateActiveNav() {
    const y = window.scrollY + 120;
    let currentId = '';
    sections.forEach((sec) => {
      if (sec.offsetTop <= y) currentId = sec.id;
    });
    navAnchors.forEach((a) => {
      a.classList.toggle('active', a.getAttribute('href') === '#' + currentId);
    });
  }
  window.addEventListener('scroll', updateActiveNav, { passive: true });

  /* ---------- Reveal on scroll ---------- */
  const reveals = document.querySelectorAll('.reveal');
  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          io.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );
  reveals.forEach((el) => io.observe(el));

  /* ---------- Counter animation ---------- */
  const counters = document.querySelectorAll('[data-count]');
  const counterIo = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const target = parseInt(el.dataset.count, 10);
        const suffix = el.dataset.suffix || '';
        const duration = 1800;
        const start = performance.now();

        function tick(now) {
          const p = Math.min((now - start) / duration, 1);
          const eased = 1 - Math.pow(1 - p, 3);
          el.textContent = Math.floor(target * eased).toLocaleString('id-ID') + suffix;
          if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
        counterIo.unobserve(el);
      });
    },
    { threshold: 0.5 }
  );
  counters.forEach((el) => counterIo.observe(el));
})();
