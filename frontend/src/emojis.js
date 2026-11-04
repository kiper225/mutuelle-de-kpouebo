const words = (text) => text.trim().split(/\s+/)

export const EMOJI_GROUPS = [
  {
    key: 'visages', label: '😀', title: 'Visages',
    items: words(`😀 😃 😄 😁 😆 😅 😂 🤣 🥲 😊 😇 🙂 🙃 😉 😌 😍 🥰 😘 😗 😙 😚 😋 😛 😜 🤪 😝 🤗 🤭 🤔 🤐
      😐 😑 😶 😏 😒 🙄 😬 😔 😪 😴 😷 🤒 🤕 🤢 🤧 🥵 🥶 🤯 🤠 🥳 😎 🤓 🧐 😕 😟 🙁 😮 😲 😳 🥺 😦 😨
      😰 😥 😢 😭 😱 😖 😣 😞 😓 😩 😫 🥱 😤 😡 😠 🤬`),
  },
  {
    key: 'gestes', label: '👍', title: 'Gestes',
    items: words(`👍 👎 👌 ✌️ 🤞 🤟 🤘 🤙 👈 👉 👆 👇 ☝️ ✋ 🤚 🖐️ 🖖 👋 🤝 🙏 ✍️ 💪 👏 🙌 👐 🤲 🫶 🤌 ✊ 👊 🤛 🤜`),
  },
  {
    key: 'coeurs', label: '❤️', title: 'Cœurs et symboles',
    items: words(`❤️ 🧡 💛 💚 💙 💜 🖤 🤍 🤎 💔 ❣️ 💕 💞 💓 💗 💖 💘 💝 💯 ✅ ❌ ⭐ 🌟 ✨ ⚡ 🔥 💥 💫 🎯 ✔️ ❗ ❓ 🕊️ ♻️`),
  },
  {
    key: 'fete', label: '🎉', title: 'Fête et activités',
    items: words(`🎉 🎊 🥳 🎈 🎁 🏆 🥇 🎂 🍾 🥂 🎵 🎶 🎤 🎧 🥁 💃 🕺 ⚽ 🏀 🏐 🎮 📣 🔔`),
  },
  {
    key: 'nature', label: '🌳', title: 'Nature et animaux',
    items: words(`🌳 🌴 🌿 🍃 🌱 🌾 🌻 🌹 🌺 🌸 🌞 🌙 ☁️ 🌧️ ⛈️ 🌈 💧 🌊 ⛰️ 🏞️ 🐔 🐐 🐄 🐕 🐈 🦅 🐘 🦁`),
  },
  {
    key: 'nourriture', label: '🍲', title: 'Nourriture',
    items: words(`🍚 🍲 🥘 🍛 🍗 🍖 🐟 🍤 🍌 🍍 🥭 🍊 🍉 🍋 🥥 🍠 🌽 🥕 🍅 🥔 🍞 🥖 🍳 ☕ 🍵 🥤 🍺 🍷 🍰 🍬`),
  },
  {
    key: 'lieux', label: '🏠', title: 'Lieux et objets',
    items: words(`🏠 🏡 🏫 🏥 🏛️ ⛪ 🕌 🛣️ 🚗 🚌 🏍️ 🚲 ✈️ 🚚 ⛽ 📱 💻 📷 💡 🔧 🔨 🧱 🚧 🏗️ 📅 📌 📝 📚 💰 💵`),
  },
]