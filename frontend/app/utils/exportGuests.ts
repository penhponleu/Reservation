// "Export Excel" → download guests as a .csv file that Excel opens directly.
// BOM at the start = Excel reads UTF-8 (Khmer names stay correct).
export function exportGuests(rows: any[], name = "guests") {
  const time = (d: string | null) => (d ? new Date(d).toLocaleString("en-GB") : "");
  const cell = (v: unknown) => `"${String(v ?? "").replace(/"/g, '""')}"`;

  const header = ["#", "Full name", "Email", "Phone", "Ticket type", "Registered", "Email sent", "Checked in"];
  const lines = rows.map((r, i) =>
    [r.no ?? rows.length - i, r.fullname, r.email, r.phone, r.ticket_type?.name, time(r.created_at), time(r.email_sent_at), time(r.checked_in_at)]
      .map(cell)
      .join(","),
  );

  const csv = "﻿" + [header.map(cell).join(","), ...lines].join("\r\n");
  const url = URL.createObjectURL(new Blob([csv], { type: "text/csv;charset=utf-8" }));
  const a = document.createElement("a");
  a.href = url;
  a.download = `${name}-${new Date().toISOString().slice(0, 10)}.csv`; // e.g. guests-2026-10-28.csv
  a.click();
  URL.revokeObjectURL(url);
}
