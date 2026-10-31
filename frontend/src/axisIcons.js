// Icônes prédéfinies pour les axes d'action et les valeurs.
// Les clés doivent rester identiques à SectionItem::ICONS côté API.
export const AXIS_ICONS = {
  sante: { label: 'Santé', svg: '<path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z"/>' },
  education: { label: 'Éducation', svg: '<path d="M2 5c3-1.5 7-1.5 10 1 3-2.5 7-2.5 10-1v14c-3-1.5-7-1.5-10 1-3-2.5-7-2.5-10-1z"/><path d="M12 6v14"/>' },
  infrastructures: { label: 'Infrastructures', svg: '<path d="M8 3 5 21M16 3l3 18M12 4v3M12 10.5v3M12 17v3"/>' },
  eau: { label: 'Eau', svg: '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/>' },
  agriculture: { label: 'Agriculture', svg: '<path d="M12 21V11"/><path d="M12 11C12 7 9 5 5 5c0 4 2 6 7 6z"/><path d="M12 14c0-3 2-5 6-5 0 3-2 5-6 5z"/>' },
  jeunesse: { label: 'Jeunesse', svg: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2c3 .2 5.5 2 5.5 5.3"/>' },
  entraide: { label: 'Entraide', svg: '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>' },
  culture: { label: 'Culture', svg: '<path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/>' },
  sport: { label: 'Sport', svg: '<circle cx="12" cy="12" r="9"/><path d="M12 3c3 3 3 15 0 18M3 12h18"/>' },
  environnement: { label: 'Environnement', svg: '<path d="M12 21v-6"/><path d="M12 15c-4 0-6-2.5-6-5.5C6 6 9 3 12 3s6 3 6 6.5c0 3-2 5.5-6 5.5z"/>' },
  emploi: { label: 'Emploi', svg: '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>' },
  habitat: { label: 'Habitat', svg: '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10M10 20v-6h4v6"/>' },
  securite: { label: 'Sécurité et paix', svg: '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>' },
  dialogue: { label: 'Dialogue', svg: '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>' },
  transparence: { label: 'Transparence', svg: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>' },
  responsabilite: { label: 'Responsabilité', svg: '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l3 3 5-6"/>' },
  union: { label: 'Union', svg: '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>' },
  building: { label: 'Bâtiment', svg: '<rect x="4" y="3" width="10" height="18" rx="1"/><path d="M14 9h5a1 1 0 0 1 1 1v11M8 7h2M8 11h2M8 15h2M17 13h.01M17 17h.01"/>' },
}