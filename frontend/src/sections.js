// Description des sections gérables depuis l'administration.
const ILLUSTRATION = {
  label: 'Image (facultative)',
  aspect: 4 / 3,
  width: 800,
  hint: "Illustre l'élément sur la page d'accueil. Vous pourrez la recadrer avant l'envoi.",
}

export const SECTION_CONFIG = {
  bureau: {
    singular: 'membre du bureau',
    plural: 'Membres du bureau',
    titleLabel: 'Nom et prénom',
    subtitleLabel: 'Fonction au bureau',
    textLabel: 'Citation ou message',
    image: { label: 'Photo', aspect: 3 / 4, width: 600, hint: "Portrait vertical : vous pourrez le recadrer avant l'envoi." },
    icons: false,
  },
  axes: {
    singular: "axe d'action",
    plural: "Axes d'action",
    titleLabel: 'Titre',
    subtitleLabel: null,
    textLabel: 'Description courte',
    image: ILLUSTRATION,
    icons: true,
  },
  valeurs: {
    singular: 'valeur',
    plural: 'Valeurs',
    titleLabel: 'Valeur',
    subtitleLabel: null,
    textLabel: 'Phrase',
    image: ILLUSTRATION,
    icons: true,
  },
}