// Textes et images du site vitrine. Remplacez les [crochets] par les vrais contenus.
// Images : placez les fichiers dans le dossier public/img puis indiquez leur chemin (ex. '/img/president.jpg').
// Laissez '' pour afficher un emplacement vide.
export const site = {
  name: 'Mutuelle de Développement de Kpouèbo',
  slogan: 'Unir, dynamiser, développer Kpouèbo',
  hero: '[Phrase d’accroche : ce que la mutuelle apporte à son village.]',
  heroImage: '/img/hero.jpg', // photo plein écran du village ou des membres
  about: '[Présentation de la mutuelle en 2 ou 3 phrases.]',
  history: '[Histoire de la mutuelle : date de création, fondateurs, grandes étapes.]',
  videoUrl: 'https://www.youtube.com/watch?v=Aa5RJkpntAM', // lien d'intégration YouTube, ex. 'https://www.youtube.com/embed/XXXXXXXX'
  footerText: '[Courte description de la mutuelle.]',
  president: {
    name: '[Nom Prénom]',
    role: 'Président de la Mutuelle',
    message: '[Message du président aux habitants et aux membres.]',
    photo: '',
  },
  axes: [
    { title: '[Axe 1]', text: '[Description courte]' },
    { title: '[Axe 2]', text: '[Description courte]' },
    { title: '[Axe 3]', text: '[Description courte]' },
    { title: '[Axe 4]', text: '[Description courte]' },
  ],
  village: {
    title: 'Une mutuelle présente pour tout le village',
    text: '[Texte sur les quartiers, familles et zones où la mutuelle intervient.]',
    image: '', // carte ou photo aérienne de Kpouèbo
  },
  values: [
    { title: 'Solidarité', text: '[Phrase]' },
    { title: 'Entraide', text: '[Phrase]' },
    { title: 'Unité', text: '[Phrase]' },
    { title: 'Transparence', text: '[Phrase]' },
    { title: 'Responsabilité', text: '[Phrase]' },
    { title: 'Développement', text: '[Phrase]' },
  ],
  bureau: [
    { name: '[Nom Prénom 1]', role: '[Fonction]', quote: '[Citation ou message du membre du bureau.]', photo: '' },
    { name: '[Nom Prénom 2]', role: '[Fonction]', quote: '[Citation ou message du membre du bureau.]', photo: '' },
    { name: '[Nom Prénom 3]', role: '[Fonction]', quote: '[Citation ou message du membre du bureau.]', photo: '' },
    { name: '[Nom Prénom 4]', role: '[Fonction]', quote: '[Citation ou message du membre du bureau.]', photo: '' },
  ],
  don: {
    intro: 'Pourquoi donner : à quels projets servent les dons.',
    howTo: 'Comment donner en attendant le paiement en ligne : contact du trésorier, numéro Mobile Money de la mutuelle, etc.',
  },
  contact: { phone: '[Téléphone]', email: '[Email]', address: 'Kpouèbo, Côte d’Ivoire' },
}
