/* ==========================================================================
   LAVAFRUTAS 360 - LANDING PAGE INTERACTIVE JAVASCRIPT
   ========================================================================== */

// Checkout URL Target - CHANGE THIS TO "/checkout" ON WORDPRESS PRODUCTION
const CHECKOUT_URL = "checkout.html";

// Review Statistics Configuration Object (Set stats here when real metrics are available)
const reviewStats = {
  average: 4.8,
  total: 124,
  fiveStars: 85,
  fourStars: 10,
  threeStars: 3,
  twoStars: 1,
  oneStar: 1
};

document.addEventListener('DOMContentLoaded', () => {
  initCheckoutLinks();
  initStickyCTA();
  initFAQAccordion();
  initGalleryLightbox();
  initReviewStats();
  initMuralLoadMore();
});

/* 1. Connect all CTA buttons to checkout URL */
function initCheckoutLinks() {
  const buyButtons = document.querySelectorAll('.js-btn-buy');
  buyButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = CHECKOUT_URL;
    });
  });
}

/* 2. Mobile Sticky CTA visibility on scroll */
function initStickyCTA() {
  const stickyBar = document.querySelector('.lp-sticky-buy');
  const heroSection = document.querySelector('.lp-hero');
  const footerSection = document.querySelector('.lp-footer');

  if (!stickyBar || !heroSection) return;

  window.addEventListener('scroll', () => {
    const heroBottom = heroSection.getBoundingClientRect().bottom;
    const footerTop = footerSection ? footerSection.getBoundingClientRect().top : Infinity;
    const windowHeight = window.innerHeight;

    // Show sticky bar only after hero section is scrolled past, hide before footer
    if (heroBottom < 0 && footerTop > windowHeight) {
      stickyBar.classList.add('visible');
    } else {
      stickyBar.classList.remove('visible');
    }
  });
}

/* 3. Accordion FAQ */
function initFAQAccordion() {
  const faqItems = document.querySelectorAll('.lp-faq-item');
  faqItems.forEach(item => {
    const question = item.querySelector('.lp-faq-question');
    question.addEventListener('click', () => {
      const isActive = item.classList.contains('active');
      // Close all other accordion items
      faqItems.forEach(otherItem => otherItem.classList.remove('active'));
      // Toggle current item
      if (!isActive) {
        item.classList.add('active');
      }
    });
  });
}

/* 4. Lightbox Modal for Gallery */
function initGalleryLightbox() {
  const galleryItems = document.querySelectorAll('.lp-galeria-item img');
  const lightbox = document.getElementById('lpLightbox');
  const lightboxImg = document.getElementById('lpLightboxImg');
  const lightboxClose = document.getElementById('lpLightboxClose');

  if (!lightbox || !lightboxImg) return;

  galleryItems.forEach(img => {
    img.addEventListener('click', () => {
      lightboxImg.src = img.src;
      lightbox.classList.add('active');
    });
  });

  if (lightboxClose) {
    lightboxClose.addEventListener('click', () => {
      lightbox.classList.remove('active');
    });
  }

  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) {
      lightbox.classList.remove('active');
    }
  });
}

/* 5. Render Overall Review Statistics Widget */
function initReviewStats() {
  const statsWidget = document.getElementById('lpStatsWidget');
  if (!statsWidget) return;

  // Hide widget if no real review stats provided
  if (!reviewStats || !reviewStats.average || !reviewStats.total) {
    statsWidget.style.display = 'none';
    return;
  }

  document.getElementById('lpStatAvg').textContent = reviewStats.average.toFixed(1);
  document.getElementById('lpStatTotal').textContent = reviewStats.total;
  
  document.getElementById('lpBar5').style.width = reviewStats.fiveStars + '%';
  document.getElementById('lpBar4').style.width = reviewStats.fourStars + '%';
  document.getElementById('lpBar3').style.width = reviewStats.threeStars + '%';
  document.getElementById('lpBar2').style.width = reviewStats.twoStars + '%';
  document.getElementById('lpBar1').style.width = reviewStats.oneStar + '%';
}

/* 6. Mural Load More Reviews */
function initMuralLoadMore() {
  const btnLoadMore = document.getElementById('btnLoadMoreReviews');
  const hiddenReviews = document.querySelectorAll('.lp-review-hidden');

  if (!btnLoadMore) return;

  btnLoadMore.addEventListener('click', () => {
    hiddenReviews.forEach(review => {
      review.style.display = 'block';
      review.classList.add('lp-fade-in');
    });
    btnLoadMore.style.display = 'none';
  });
}
