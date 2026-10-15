
import { mount } from "./dom.js";
import { Button } from "./components/button.js";
import { Card } from "./components/card.js";
import { Tabs } from "./components/tabs.js";
import { openModal } from "./components/modal.js";
import { showToast } from "./components/toast.js";

const app = document.getElementById("app");

const jobsTabs = Tabs({
  items: [
    { id: "completed", label: "Completed" },
    { id: "active", label: "Active" },
    { id: "hold", label: "On Hold" },
    { id: "posted", label: "Posted" },
  ],
  active: "active",
  onChange: (id) => showToast(`Switched to ${id}`),
});

let dialog;
const card = Card(
  { hover: true },
  "YouTube Episode #12",
  Button({
    label: "View Job",
    size: "sm",
    onClick: () => {
      dialog = openModal({
        title: "YouTube Episode #12",
        content: "Deadline Oct 04 · 72% complete",
        actions: [
          Button({
            label: "Close",
            variant: "ghost",
            onClick: () => dialog.close(),
          }),
        ],
      });
    },
  }),
);

mount(app, Card({}, jobsTabs, card));
