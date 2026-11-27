<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    /**
     * The ten Basiit systems shown on the website.
     * Safe to run again: it updates the same systems by slug.
     */
    public function run(): void
    {
        // Remove the practice samples from the first version of this seeder.
        Project::whereIn('slug', [
            'courier-delivery-app',
            'bakery-online-menu',
            'clinic-booking-system',
            'school-results-portal',
            'inventory-dashboard',
            'portfolio-redesign',
        ])->delete();

        $systems = [
            ['Cargo System', '#2f8be6', 'truck', 'Logistics', 'Bookings, AWB tracking, warehousing and freight billing for forwarders and ground handlers.'],
            ['SME System', '#38bdf8', 'bag', 'Business', 'Invoices, quotations, stock and payroll in one lean workspace built for growing teams.'],
            ['Airline System', '#a78bfa', 'plane', 'Aviation', 'Flight schedules, crew rostering, ticketing reconciliation and route profitability.'],
            ['Hospital System', '#34d399', 'medical', 'Healthcare', 'Patients, beds, EMR, lab, pharmacy and insurance claims connected end to end.'],
            ['Microfinance System', '#fbbf24', 'money', 'Finance', 'Loan origination, group lending, collections and field-officer mobility out of the box.'],
            ['Clinic System', '#fb7185', 'heartbeat', 'Healthcare', 'Appointments, consultations, vitals and billing for small practices and polyclinics.'],
            ['Travel Agency', '#22d3ee', 'globe', 'Travel', 'Packages, itineraries, supplier rates, visa files and commissions in one console.'],
            ['POS System', '#f472b6', 'register', 'Retail', 'Blazing-fast counter sales, barcode stock, shift close and live outlet analytics.'],
            ['Corporate System', '#818cf8', 'briefcase', 'Corporate', 'HR, leave, projects, approval chains and board-ready reporting for head offices.'],
            ['Financial System', '#4ade80', 'chart', 'Finance', 'General ledger, payables, receivables, budgets and consolidated multi-entity accounts.'],
        ];

        foreach ($systems as $i => [$title, $color, $icon, $tag, $summary]) {
            Project::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'summary' => $summary,
                    'color' => $color,
                    'icon' => $icon,
                    'tags' => [$tag],
                    'status' => 'published',
                    'featured' => false,
                    'sort_order' => $i + 1,
                ],
            );
        }
    }
}