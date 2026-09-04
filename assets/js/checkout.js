/* ==========================================================================
   LAVAFRUTAS 360 - CHECKOUT LOGIC & PIX INTEGRATION
   ========================================================================== */

const PRODUCT_BASE_PRICE = 19.90;
let currentQuantity = 1;
let currentShippingPrice = 0.00;
let currentTotal = 19.90;
let pixCheckInterval = null;

document.addEventListener('DOMContentLoaded', () => {
  initMasks();
  initQuantitySelector();
  initViaCEP();
  initFormDrafting();
  initCheckoutFormSubmit();
  updateTotals();
});

/* 1. Input Masks */
function initMasks() {
  const cpfInput = document.getElementById('cpf');
  const phoneInput = document.getElementById('phone');
  const cepInput = document.getElementById('cep');

  if (cpfInput) {
    cpfInput.addEventListener('input', (e) => {
      let v = e.target.value.replace(/\D/g, '');
      if (v.length > 11) v = v.substring(0, 11);
      v = v.replace(/(\d{3})(\d)/, '$1.$2');
      v = v.replace(/(\d{3})(\d)/, '$1.$2');
      v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
      e.target.value = v;
    });
  }

  if (phoneInput) {
    phoneInput.addEventListener('input', (e) => {
      let v = e.target.value.replace(/\D/g, '');
      if (v.length > 11) v = v.substring(0, 11);
      if (v.length > 10) {
        v = v.replace(/^(\d\d)(\d{5})(\d{4})$/, '($1) $2-$3');
      } else {
        v = v.replace(/^(\d\d)(\d{4})(\d{4})$/, '($1) $2-$3');
      }
      e.target.value = v;
    });
  }

  if (cepInput) {
    cepInput.addEventListener('input', (e) => {
      let v = e.target.value.replace(/\D/g, '');
      if (v.length > 8) v = v.substring(0, 8);
      v = v.replace(/^(\d{5})(\d)/, '$1-$2');
      e.target.value = v;

      if (v.replace(/\D/g, '').length === 8) {
        fetchAddressByCEP(v.replace(/\D/g, ''));
      }
    });
  }
}

/* 2. Quantity Selector */
function initQuantitySelector() {
  const btnMinus = document.getElementById('btnQtyMinus');
  const btnPlus = document.getElementById('btnQtyPlus');
  const qtyVal = document.getElementById('qtyVal');

  if (!btnMinus || !btnPlus) return;

  btnMinus.addEventListener('click', () => {
    if (currentQuantity > 1) {
      currentQuantity--;
      qtyVal.textContent = currentQuantity;
      updateTotals();
    }
  });

  btnPlus.addEventListener('click', () => {
    currentQuantity++;
    qtyVal.textContent = currentQuantity;
    updateTotals();
  });
}

/* 3. ViaCEP API Integration & Freight Calculation Hook */
function fetchAddressByCEP(cleanCEP) {
  const cepFeedback = document.getElementById('cepFeedback');
  const streetInput = document.getElementById('street');
  const neighborhoodInput = document.getElementById('neighborhood');
  const cityInput = document.getElementById('city');
  const stateInput = document.getElementById('state');

  if (cepFeedback) cepFeedback.textContent = "Buscando endereço e calculando frete...";

  fetch(`https://viacep.com.br/ws/${cleanCEP}/json/`)
    .then(res => res.json())
    .then(data => {
      if (data.erro) {
        if (cepFeedback) cepFeedback.textContent = "CEP não encontrado. Digite o endereço manualmente.";
        return;
      }
      if (streetInput) streetInput.value = data.logradouro || '';
      if (neighborhoodInput) neighborhoodInput.value = data.bairro || '';
      if (cityInput) cityInput.value = data.localidade || '';
      if (stateInput) stateInput.value = data.uf || '';

      if (cepFeedback) cepFeedback.textContent = "✓ Endereço preenchido com sucesso!";
      
      // Calculate freight price via API hook
      calculateShipping(cleanCEP, data.uf);
    })
    .catch(() => {
      if (cepFeedback) cepFeedback.textContent = "Erro ao buscar CEP. Preencha manualmente.";
    });
}

/* 4. Freight Calculation Hook (Integration Ready) */
function calculateShipping(cep, state) {
  const shippingContainer = document.getElementById('shippingOptionsContainer');
  if (shippingContainer) {
    shippingContainer.style.display = 'block';
    shippingContainer.innerHTML = '<div style="font-size: 0.85rem; color: #64748B;">Calculando opções de entrega...</div>';
  }

  // =========================================================================
  // INSERIR INTEGRAÇÃO DA API DE FRETE AQUI (Ex: Melhor Envio / Frenet / Correios)
  // =========================================================================
  /*
     Exemplo de chamada real de API de Frete:
     fetch('/api/frete', {
       method: 'POST',
       headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify({ cep, state, qty: currentQuantity })
     })
     .then(res => res.json())
     .then(options => renderShippingOptions(options));
  */

  // Default fallback behavior for demonstration
  setTimeout(() => {
    // Standard regional shipping rate estimation
    let estimatedPrice = 14.90;
    if (['SP', 'RJ', 'MG', 'ES'].includes(state)) {
      estimatedPrice = 12.50;
    } else if (['PR', 'SC', 'RS'].includes(state)) {
      estimatedPrice = 15.90;
    } else {
      estimatedPrice = 18.90;
    }

    currentShippingPrice = estimatedPrice;
    
    if (shippingContainer) {
      shippingContainer.innerHTML = `
        <div class="shipping-option-item selected">
          <div>
            <strong>Entrega Padrão (Sedex/PAC)</strong>
            <div style="font-size: 0.8rem; color: #64748B;">Prazo estimado: 3 a 7 dias úteis</div>
          </div>
          <strong style="color: var(--primary-pink)">R$ ${estimatedPrice.toFixed(2).replace('.', ',')}</strong>
        </div>
      `;
    }
    updateTotals();
  }, 400);
}

/* 5. Update Order Summary Totals */
function updateTotals() {
  const productSubtotal = PRODUCT_BASE_PRICE * currentQuantity;
  currentTotal = productSubtotal + currentShippingPrice;

  const elemSubtotal = document.getElementById('summarySubtotal');
  const elemShipping = document.getElementById('summaryShipping');
  const elemTotal = document.getElementById('summaryTotal');
  const elemQtyDisplay = document.getElementById('summaryQtyDisplay');

  if (elemQtyDisplay) elemQtyDisplay.textContent = currentQuantity;
  if (elemSubtotal) elemSubtotal.textContent = `R$ ${productSubtotal.toFixed(2).replace('.', ',')}`;
  if (elemShipping) {
    elemShipping.textContent = currentShippingPrice > 0 
      ? `R$ ${currentShippingPrice.toFixed(2).replace('.', ',')}` 
      : 'Calculando...';
  }
  if (elemTotal) elemTotal.textContent = `R$ ${currentTotal.toFixed(2).replace('.', ',')}`;
}

/* 6. LocalStorage Draft Preservation (Abandonment Protection) */
function initFormDrafting() {
  const fields = ['fullName', 'email', 'phone', 'cpf', 'cep', 'street', 'number', 'complement', 'neighborhood', 'city', 'state'];
  
  // Restore saved draft
  fields.forEach(fieldId => {
    const elem = document.getElementById(fieldId);
    if (elem) {
      const saved = localStorage.getItem(`draft_${fieldId}`);
      if (saved) elem.value = saved;

      elem.addEventListener('input', () => {
        localStorage.setItem(`draft_${fieldId}`, elem.value);
      });
    }
  });
}

/* 7. Checkout Form Validation & PIX Generation */
function initCheckoutFormSubmit() {
  const btnSubmit = document.getElementById('btnSubmitOrder');
  if (!btnSubmit) return;

  btnSubmit.addEventListener('click', (e) => {
    e.preventDefault();
    if (!validateCheckoutForm()) return;

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner"></span> Redirecionando...';

    const formData = {
      name: document.getElementById('fullName').value.trim(),
      email: document.getElementById('email').value.trim(),
      phone: document.getElementById('phone').value.trim(),
      cpf: document.getElementById('cpf').value.trim(),
      cep: document.getElementById('cep').value.trim(),
      street: document.getElementById('street').value.trim(),
      number: document.getElementById('number').value.trim(),
      complement: document.getElementById('complement').value.trim(),
      neighborhood: document.getElementById('neighborhood').value.trim(),
      city: document.getElementById('city').value.trim(),
      state: document.getElementById('state').value.trim(),
      quantity: currentQuantity,
      shippingPrice: currentShippingPrice,
      totalAmount: currentTotal
    };

    // Dispara o evento de InitiateCheckout na API de Conversões, depois redireciona
    fetch('api-fb-capi.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    }).finally(() => {
      // Redirecionamento para a Invictus Pay com os dados preenchidos
      const baseUrl = 'https://checkout.invictuspayv2.com.br/c/off_01m1q3vsv084pmkekf1jzmtf8e';
      const params = new URLSearchParams({
        name: formData.name,
        email: formData.email,
        document: formData.cpf,
        phone: formData.phone,
        zipcode: formData.cep,
        street: formData.street,
        number: formData.number,
        neighborhood: formData.neighborhood,
        city: formData.city,
        state: formData.state
      });

      window.location.href = `${baseUrl}?${params.toString()}`;
    });
  });
}

/* 8. Validate Required Fields */
function validateCheckoutForm() {
  const required = ['fullName', 'email', 'phone', 'cpf', 'cep', 'street', 'number', 'neighborhood', 'city', 'state'];
  let isValid = true;

  required.forEach(id => {
    const elem = document.getElementById(id);
    if (!elem || !elem.value.trim()) {
      if (elem) elem.classList.add('error');
      isValid = false;
    } else {
      if (elem) elem.classList.remove('error');
    }
  });

  if (!isValid) {
    alert('Por favor, preencha todos os campos obrigatórios em vermelho para prosseguir.');
  }
  return isValid;
}

/* 9. Render PIX Box (Production Result) */
function renderPIXBox(pixCode, qrCodeUrl, txid) {
  const pixContainer = document.getElementById('pixResultContainer');
  if (!pixContainer) return;

  pixContainer.style.display = 'block';
  pixContainer.innerHTML = `
    <div class="pix-box">
      <h3 style="color: var(--primary-pink); font-size: 1.3rem;">PIX Gerado com Sucesso! ⚡</h3>
      <p style="font-size: 0.9rem; color: #64748B; margin-top: 4px;">Abra o app do seu banco e escaneie o QR Code ou copie a chave abaixo:</p>
      
      <div class="pix-qr-img">
        <img src="${qrCodeUrl}" alt="QR Code PIX" style="width: 100%; height: 100%;">
      </div>

      <input type="text" class="pix-code-input" id="pixCodeInput" value="${pixCode}" readonly>
      
      <button class="lp-btn" id="btnCopyPix" style="min-height: 44px; font-size: 0.95rem;">
        📋 COPIAR CÓDIGO PIX
      </button>

      <div class="pix-status-badge" id="pixStatusBadge">
        ⏳ Aguardando pagamento do PIX...
      </div>
    </div>
  `;

  bindCopyPixBtn(pixCode);
  startPixStatusPolling(txid);
}

/* 10. Render PIX Box (Static Demo Fallback) */
function renderPIXBoxDemo(formData) {
  const pixContainer = document.getElementById('pixResultContainer');
  if (!pixContainer) return;

  const demoPixCode = "00020126580014BR.GOV.BCB.PIX0136lavafrutas360-pix-key-demo520400005303986540519.905802BR5925LavaFrutas 360 Loja Oficial6009Sao Paulo62070503***6304E2D1";
  const demoQrCode = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(demoPixCode)}`;

  pixContainer.style.display = 'block';
  pixContainer.innerHTML = `
    <div class="pix-box">
      <h3 style="color: var(--primary-pink); font-size: 1.3rem;">PIX Gerado com Sucesso! ⚡</h3>
      <p style="font-size: 0.9rem; color: #64748B; margin-top: 4px;">Abra o aplicativo do seu banco e escaneie o QR Code ou copie o código abaixo:</p>
      
      <div class="pix-qr-img">
        <img src="${demoQrCode}" alt="QR Code PIX Demo" style="width: 100%; height: 100%;">
      </div>

      <p style="font-weight: 800; font-size: 1.1rem; color: var(--text-dark); margin-bottom: 8px;">
        Valor a Pagar: R$ ${formData.totalAmount.toFixed(2).replace('.', ',')}
      </p>

      <input type="text" class="pix-code-input" id="pixCodeInput" value="${demoPixCode}" readonly>
      
      <button class="lp-btn" id="btnCopyPix" style="min-height: 48px; font-size: 0.95rem;">
        📋 COPIAR CÓDIGO PIX
      </button>

      <div class="pix-status-badge" id="pixStatusBadge">
        ⏳ Aguardando confirmação do pagamento...
      </div>
    </div>
  `;

  bindCopyPixBtn(demoPixCode);

  // Simulate payment confirmation for demonstration
  setTimeout(() => {
    const badge = document.getElementById('pixStatusBadge');
    if (badge) {
      badge.className = "pix-status-badge success";
      badge.innerHTML = "Pagamento confirmado com sucesso! ✅";
    }
  }, 12000);
}

/* 11. Copy PIX Code to Clipboard with User Feedback */
function bindCopyPixBtn(pixCode) {
  const btn = document.getElementById('btnCopyPix');
  if (!btn) return;

  btn.addEventListener('click', () => {
    navigator.clipboard.writeText(pixCode).then(() => {
      btn.innerHTML = 'Código copiado ✓';
      btn.style.backgroundColor = '#10B981';
      setTimeout(() => {
        btn.innerHTML = '📋 COPIAR CÓDIGO PIX';
        btn.style.backgroundColor = 'var(--primary-pink)';
      }, 3000);
    });
  });
}

/* 12. Poll Payment Status via Backend */
function startPixStatusPolling(txid) {
  if (!txid) return;
  pixCheckInterval = setInterval(() => {
    fetch(`api-pix.php?action=check_status&txid=${txid}`)
      .then(res => res.json())
      .then(res => {
        if (res && res.status === 'APPROVED') {
          clearInterval(pixCheckInterval);
          const badge = document.getElementById('pixStatusBadge');
          if (badge) {
            badge.className = "pix-status-badge success";
            badge.innerHTML = "Pagamento confirmado com sucesso! ✅";
          }
        }
      });
  }, 5000);
}
