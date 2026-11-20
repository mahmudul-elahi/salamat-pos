// Deliberately localStorage, shared across every tab of the same origin —
// the warehouse selection is meant to sync live across all open tabs. When
// one tab switches, the native "storage" event fires in every *other* open
// tab (never the tab that made the change), and App.vue listens for it to
// reload those tabs onto the newly selected warehouse.
export const setTenant = (tenant) => {
    return localStorage.setItem('tenant', tenant?.slug)
}

export const getTenant = () => {
    return localStorage.getItem('tenant')
}

export const removeTenant = () => {
    return localStorage.removeItem('tenant')
}