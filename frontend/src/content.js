// Textes et images du site vitrine. Remplacez les [crochets] par les vrais contenus.
// Images : placez les fichiers dans le dossier public/img puis indiquez leur chemin (ex. '/img/president.jpg').
// Laissez '' pour afficher un emplacement vide.
export const site = {
  name: 'Mutuelle de Développement de Kpouèbo',
  slogan: 'Unir, dynamiser, développer Kpouèbo',
  hero: 'Une jeunesse au coeur du développement de son village.',
  heroImage: '/img/hero.jpg', // photo plein écran du village ou des membres
  about: 'notre mutuelle a pour objectif de rassembler les jeunes du village et de la diaspora afin de promouvoir le développement économique, social et culturel de Kpouèbo.',
  history: '[Histoire de la mutuelle : date de création, fondateurs, grandes étapes.]',
  videoUrl: 'https://www.youtube.com/watch?v=Aa5RJkpntAM', // lien d'intégration YouTube, ex. 'https://www.youtube.com/embed/XXXXXXXX'
  footerText: '© 2024 Mutuelle de Développement de Kpouèbo.',
  footerRealisation: 'Réalisé par GERVAIS KIPRE. Tous droits réservés.',
  president: {
    name: 'M. KONAN Bah Marcel',
    role: 'Président du Comité ADHOC de la Mutuelle du RUMBO.',
    message: 'Chers membres, chers adhérents, chers collègues, c’est avec un grand plaisir et un profond sentiment de responsabilité que je m\'adresse à vous aujourd\'hui en ma qualité de Président de la mutuelle de Kpouèbo. Je tiens tout d\'abord à vous exprimer mes sincères remerciements pour votre confiance, votre engagement et votre participation active à la vie de notre mutuelle. C\’est grâce à la force de notre union et à notre esprit de solidarité que nous parvenons à relever les défis et à faire grandir notre structure.',
    photo: '',
  },
  axes: [
    { title: '[Axe 1]', text: '[Description courte]' },
    { title: '[Axe 2]', text: '[Description courte]' },
    { title: '[Axe 3]', text: '[Description courte]' },
    { title: '[Axe 4]', text: '[Description courte]' },
  ],
  village: {
    title: 'Un village au cœur de la Côte d\'Ivoire',
    text: 'Kpouèbo est un village du centre de la Côte d\'Ivoire, appartenant au district des Lacs, ayant pour chef-lieu de Toumodi, dans la Région du Bélier. Kpouèbo est composée de 3 quartiers : Alloubo, Yakro et Idjébo.',
    image: '../public/img/village.jpeg', // carte ou photo aérienne de Kpouèbo
  },
  values: [
    { 
      icon: '🤝', 
      title: 'Solidarité', text: 'Favoriser l\’écoute et la concertation pour construire ensemble.' 
    },
    { icon: '🤝', title: 'Entraide', text: 'Soutenir les membres dans leurs difficultés et promouvoir le soutien mutuel.' },
    { icon: '📢', title: 'Transparence', text: 'Assurer la communication claire et ouverte sur les décisions et les actions de la mutuelle.' },
    { icon: '✅', title: 'Responsabilité', text: 'Assumer ses engagements et agir avec intégrité dans l\’exercice de ses fonctions.' },
    { icon: '🌱', title: 'Développement', text: 'Promouvoir le développement économique et social du village.' },
  ],
  bureau: [
    { name: 'M. KONAN Bah Marcel', role: 'Président du Comité ADHOC du RUMBO.', quote: '[Citation ou message du membre du bureau.]', photo: '' },
    { name: '[Nom Prénom 2]', role: '[Fonction]', quote: '[Citation ou message du membre du bureau.]', photo: '' },
    { name: '[Nom Prénom 3]', role: '[Fonction]', quote: '[Citation ou message du membre du bureau.]', photo: '' },
    { name: '[Nom Prénom 4]', role: '[Fonction]', quote: '[Citation ou message du membre du bureau.]', photo: '' },
    { name: '[Nom Prénom 5]', role: '[Fonction]', quote: '[Citation ou message du membre du bureau.]', photo: '' },
    { name: '[Nom Prénom 6]', role: '[Fonction]', quote: '[Citation ou message du membre du bureau.]', photo: '' },
  ],
  don: {
    intro: 'Pourquoi donner : à quels projets servent les dons.',
    howTo: 'Comment donner en attendant le paiement en ligne : contact du trésorier, numéro Mobile Money de la mutuelle, etc.',
  },
  contact: { phone: '(+225) 21 21 21 21 21 - (+225) 07 07 07 07 07', email: 'mutuelledekpouebo@gmail.com', address: 'Kpouèbo, Côte d’Ivoire' },

  // Membre du bureau désigné pour signer la fiche, la carte et le carton annuel
  signataire: { name: '[Nom Prénom du membre du bureau désigné]', role: '[Fonction au bureau]' },
  engagement: '[Texte d’engagement du membre : respect des statuts et du règlement intérieur de la mutuelle.]',
  badgeNotice: 'Carte strictement personnelle. En cas de perte, prévenir le bureau de la mutuelle.',

  // Diapositives du carrousel de l'accueil (image : chemin dans public/img, ex. '/img/slide1.jpg', ou '' pour un fond vert)
  slides: [
    {
      eyebrow: 'UNE SEULE MISSION',
      title: 'Former, Éduquer, dynamiser la jeunesse pour développer Kpouèbo',
      text: 'Notre mutuelle a pour objectif de rassembler les jeunes du village et de la diaspora afin de promouvoir le développement économique, social et culturel de Kpouèbo.',
      image: '../public/img/eleve.webp',
      cta: { label: 'Devenir membre', to: '/inscription' },
      cta2: { label: 'Faire un don', to: '/don' },
    },
    {
      eyebrow: 'NOS CHANTIERS',
      title: 'Bâtir ensemble pour un village plus fort',
      text: 'Nos projets en cours visent à améliorer les infrastructures et les services dans notre communauté.',
      image: '../public/img/centresante.jpg',
      cta: { label: 'Voir les projets', to: '/chantiers?category=projet_en_cours' },
    },
    {
      eyebrow: 'ENSEMBLE',
      title: 'Tous unis pour une seule cause',
      text: 'Chez nous, la solidarité est une valeur fondamentale. Nous croyons que l’union fait la force et que chaque membre a un rôle à jouer dans le développement de notre village.',
      image: '../public/img/hero.jpg',
      cta: { label: 'Faire un don', to: '/don' },
    },
  ],
  // Réseaux sociaux affichés dans le bandeau vert (laissez url vide pour ne pas les afficher)
  social: [
    { label: 'Facebook', url: '' },
    { label: 'Instagram', url: '' },
    { label: 'YouTube', url: '' },
  ],

  // Section « Visages emblématiques de Kpouèbo » (photo : fichier dans public/img, ex. '/img/visage1.jpg', ou '' pour l'initiale)
  faces: {
    title: 'Visages emblématiques de Kpouèbo',
    intro: '[Phrase d’introduction : ces femmes et ces hommes qui ont marqué l’histoire et la vie du village.]',
    items: [
      { name: 'KOFFI BENOIT AHOSSI', role: 'Cadre de Kpouèbo', text: '[Quelques mots sur son parcours et sur ce qu’il ou elle a apporté à Kpouèbo.]', photo: '../public/img/ahossi.jpeg' },
      { name: 'KOUAKOU TELESFORT SARAKA', role: 'Cadre de Kpouèbo', text: '[Quelques mots sur son parcours et sur ce qu’elle a apporté à Kpouèbo.]', photo: '../public/img/telesfort.jfif' },
      { name: 'KOUAKOU RAYMOND KONAN', role: 'Cadre de Kpouèbo', text: '[Quelques mots sur son parcours et sur ce qu’il a apporté à Kpouèbo.]', photo: '../public/img/raymond2.jpg' },
      { name: 'NANAN MOLOU II', role: 'Defunt Chef du village de Kpouèbo', text: '[Quelques mots sur son parcours et sur ce qu’elle a apporté à Kpouèbo.]', photo: '../public/img/molou.jpeg' },
      { name: 'KONAN KOFFI ALBERT', role: 'Membre du bureau', text: '[Quelques mots sur son parcours et sur ce qu’il a apporté à Kpouèbo.]', photo: '../public/img/hounkpe.jpg' },
      { name: 'BROU ADOLPHE KOUAME', role: 'Cadre de Kpouèbo', text: '[Quelques mots sur son parcours et sur ce qu’elle a apporté à Kpouèbo.]', photo: '../public/img/adolphe.jpg' },
      { name: 'DIDIER ADJOUTEY', role: 'Membre du bureau', text: '[Quelques mots sur son parcours et sur ce qu’il a apporté à Kpouèbo.]', photo: '../public/img/adjoutey.jpg' },
      { name: 'FABIENNE AGBONLAHIN', role: 'Membre du bureau', text: '[Quelques mots sur son parcours et sur ce qu’elle a apporté à Kpouèbo.]', photo: '../public/img/agbonlahin.jpg' },
      { name: 'THIERRY AGBONLAHIN', role: 'Membre du bureau', text: '[Quelques mots sur son parcours et sur ce qu’il a apporté à Kpouèbo.]', photo: '../public/img/agbonlahin2.jpg' },
      { name: 'CLAUDETTE HOUNKPE', role: 'Membre du bureau', text: '[Quelques mots sur son parcours et sur ce qu’elle a apporté à Kpouèbo.]', photo: '../public/img/hounkpe.jpg' },
    ],
  },
}
