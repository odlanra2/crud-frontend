<script setup>
defineProps({
  stats: {
    type: Object,
    default: null,
  },
  cargando: Boolean,
})

const formatoNumero = new Intl.NumberFormat('es-CO')
const formatoMoneda = new Intl.NumberFormat('es-CO', {
  style: 'currency',
  currency: 'COP',
  maximumFractionDigits: 0,
})
</script>

<template>
  <section class="stats" aria-label="Resumen del inventario">
    <article class="panel stat">
      <span class="stat__icono stat__icono--verde" aria-hidden="true">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 8 12 3 3 8l9 5 9-5Z" />
          <path d="M3 8v8l9 5 9-5V8" />
          <path d="M12 13v8" />
        </svg>
      </span>
      <div>
        <p>Total productos</p>
        <strong>{{ stats ? formatoNumero.format(stats.total) : '—' }}</strong>
      </div>
    </article>

    <article class="panel stat">
      <span class="stat__icono stat__icono--azul" aria-hidden="true">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9" />
          <path d="m8.5 12 2.5 2.5 4.5-5" />
        </svg>
      </span>
      <div>
        <p>Activos</p>
        <strong>{{ stats ? formatoNumero.format(stats.activos) : '—' }}</strong>
      </div>
    </article>

    <article class="panel stat">
      <span class="stat__icono stat__icono--ambar" aria-hidden="true">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="7" width="18" height="13" rx="2" />
          <path d="M3 11h18M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
        </svg>
      </span>
      <div>
        <p>Stock total</p>
        <strong>{{ stats ? formatoNumero.format(stats.stockTotal) : '—' }}</strong>
      </div>
    </article>

    <article class="panel stat">
      <span class="stat__icono stat__icono--violeta" aria-hidden="true">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9" />
          <path d="M14.8 9.2c-.4-.7-1.4-1.2-2.6-1.2-1.6 0-2.7.8-2.7 2s1 1.7 2.7 2c1.9.4 2.9.9 2.9 2.2 0 1.3-1.2 2.1-2.9 2.1-1.4 0-2.5-.6-2.9-1.4" />
          <path d="M12 6.5v11" />
        </svg>
      </span>
      <div>
        <p>Valor inventario</p>
        <strong class="stat__valor">{{ stats ? formatoMoneda.format(stats.valorInventario) : '—' }}</strong>
      </div>
    </article>
  </section>
</template>

<style scoped>
.stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

.stat {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px;
}

.stat__icono {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  flex: none;
}

.stat__icono--verde {
  background: var(--acento-suave);
  color: var(--acento-oscuro);
}

.stat__icono--azul {
  background: #eff6ff;
  color: #1d4ed8;
}

.stat__icono--ambar {
  background: #fffbeb;
  color: #b45309;
}

.stat__icono--violeta {
  background: #f5f3ff;
  color: #6d28d9;
}

.stat p {
  font-size: 12.5px;
  color: var(--texto-suave);
  font-weight: 600;
}

.stat strong {
  display: block;
  font-size: 22px;
  letter-spacing: -0.4px;
  margin-top: 2px;
  font-variant-numeric: tabular-nums;
}

.stat__valor {
  font-size: 19px;
}

@media (max-width: 980px) {
  .stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 560px) {
  .stats {
    grid-template-columns: 1fr;
  }
}
</style>
