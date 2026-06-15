<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerMeetingController extends Controller
{
    /**
     * GET /api/owner/meetings
     * Parameter opsional:
     *   ?start_date=2023-12-01
     *   ?end_date=2023-12-31
     *   ?sort=asc|desc (default: desc)
     */
    public function index(Request $request): JsonResponse
    {
        $sort      = $request->query('sort', 'desc') === 'asc' ? 'ASC' : 'DESC';
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        // Bangun kondisi WHERE dinamis
        $where  = "WHERE h.meeting = 'Owner'";
        $params = [];

        if ($startDate) {
            $where   .= " AND h.tgl >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $where   .= " AND h.tgl <= ?";
            $params[] = $endDate;
        }

        $meetings = DB::select("
            SELECT
                h.id_mom,
                h.judul,
                h.tempat,
                h.tgl,
                h.start_time,
                h.end_time,
                h.meeting AS jenis_meeting,

                i.id        AS issue_id,
                i.topik     AS hasil_topik,
                i.issue,

                ii.id               AS item_id,
                ii.action,
                ii.kategori,
                ii.level,
                ii.deadline,
                ii.pic,
                ii.ekspektasi,
                ii.owner_verified_status,
                ii.owner_verified_note,
                ii.owner_verified_by,
                ii.owner_verified_at,
                ii.owner_meeting

            FROM mom_header h
            LEFT JOIN mom_issue i
                ON h.id_mom = i.id_mom
            LEFT JOIN mom_issue_item ii
                ON i.id_mom = ii.id_mom
                AND i.id_issue = ii.id_issue

            $where
            ORDER BY h.tgl $sort, h.id_mom, i.id, ii.id
        ", $params);

        // Grouping: per meeting → per issue → items
        $grouped = [];
        foreach ($meetings as $row) {
            $idMom = $row->id_mom;

            if (!isset($grouped[$idMom])) {
                $grouped[$idMom] = [
                    'id_mom'        => $row->id_mom,
                    'judul'         => $row->judul,
                    'tempat'        => $row->tempat,
                    'tgl'           => $row->tgl,
                    'start_time'    => $row->start_time,
                    'end_time'      => $row->end_time,
                    'jenis_meeting' => $row->jenis_meeting,
                    'issues'        => [],
                ];
            }

            if ($row->issue_id) {
                $issueId = $row->issue_id;
                if (!isset($grouped[$idMom]['issues'][$issueId])) {
                    $grouped[$idMom]['issues'][$issueId] = [
                        'issue_id'    => $row->issue_id,
                        'hasil_topik' => $row->hasil_topik,
                        'issue'       => $row->issue,
                        'items'       => [],
                    ];
                }

                if ($row->item_id) {
                    $grouped[$idMom]['issues'][$issueId]['items'][] = [
                        'item_id'               => $row->item_id,
                        'action'                => $row->action,
                        'kategori'              => $row->kategori,
                        'level'                 => $row->level,
                        'deadline'              => $row->deadline,
                        'pic'                   => $row->pic,
                        'ekspektasi'            => $row->ekspektasi,
                        'owner_verified_status' => $row->owner_verified_status,
                        'owner_verified_note'   => $row->owner_verified_note,
                        'owner_verified_by'     => $row->owner_verified_by,
                        'owner_verified_at'     => $row->owner_verified_at,
                        'owner_meeting'         => $row->owner_meeting,
                    ];
                }
            }
        }

        // Reset array keys
        $result = array_values(array_map(function ($m) {
            $m['issues'] = array_values($m['issues']);
            foreach ($m['issues'] as &$issue) {
                $issue['items'] = array_values($issue['items']);
            }
            return $m;
        }, $grouped));

        return response()->json([
            'success'    => true,
            'total'      => count($result),
            'filters'    => [
                'start_date' => $startDate ?? 'semua',
                'end_date'   => $endDate   ?? 'semua',
                'sort'       => strtolower($sort),
            ],
            'data'       => $result,
        ]);
    }

    /**
     * GET /api/owner/meetings/{id_mom}
     * Detail 1 meeting Owner by id_mom
     */
    public function show(string $idMom): JsonResponse
    {
        $rows = DB::select("
            SELECT
                h.id_mom,
                h.judul,
                h.tempat,
                h.tgl,
                h.start_time,
                h.end_time,
                h.meeting AS jenis_meeting,

                i.id        AS issue_id,
                i.topik     AS hasil_topik,
                i.issue,

                ii.id               AS item_id,
                ii.action,
                ii.kategori,
                ii.level,
                ii.deadline,
                ii.pic,
                ii.ekspektasi,
                ii.owner_verified_status,
                ii.owner_verified_note,
                ii.owner_verified_by,
                ii.owner_verified_at,
                ii.owner_meeting

            FROM mom_header h
            LEFT JOIN mom_issue i    ON h.id_mom = i.id_mom
            LEFT JOIN mom_issue_item ii
                ON i.id_mom = ii.id_mom AND i.id_issue = ii.id_issue

            WHERE h.meeting = 'Owner'
              AND h.id_mom = ?

            ORDER BY i.id, ii.id
        ", [$idMom]);

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'Meeting tidak ditemukan atau bukan tipe Owner.',
            ], 404);
        }

        $meeting = null;
        $issues  = [];

        foreach ($rows as $row) {
            if (!$meeting) {
                $meeting = [
                    'id_mom'        => $row->id_mom,
                    'judul'         => $row->judul,
                    'tempat'        => $row->tempat,
                    'tgl'           => $row->tgl,
                    'start_time'    => $row->start_time,
                    'end_time'      => $row->end_time,
                    'jenis_meeting' => $row->jenis_meeting,
                ];
            }

            if ($row->issue_id) {
                if (!isset($issues[$row->issue_id])) {
                    $issues[$row->issue_id] = [
                        'issue_id'    => $row->issue_id,
                        'hasil_topik' => $row->hasil_topik,
                        'issue'       => $row->issue,
                        'items'       => [],
                    ];
                }
                if ($row->item_id) {
                    $issues[$row->issue_id]['items'][] = [
                        'item_id'               => $row->item_id,
                        'action'                => $row->action,
                        'kategori'              => $row->kategori,
                        'level'                 => $row->level,
                        'deadline'              => $row->deadline,
                        'pic'                   => $row->pic,
                        'ekspektasi'            => $row->ekspektasi,
                        'owner_verified_status' => $row->owner_verified_status,
                        'owner_verified_note'   => $row->owner_verified_note,
                        'owner_verified_by'     => $row->owner_verified_by,
                        'owner_verified_at'     => $row->owner_verified_at,
                        'owner_meeting'         => $row->owner_meeting,
                    ];
                }
            }
        }

        $meeting['issues'] = array_values($issues);

        return response()->json([
            'success' => true,
            'data'    => $meeting,
        ]);
    }

    /**
     * PUT /api/owner/meetings/{id_mom}/verify/{item_id}
     * Owner approve/reject item MOM
     * Body: { "status": 1|2, "note": "...", "owner_meeting": "2026-06-12" }
     */
    public function verify(Request $request, string $idMom, int $itemId): JsonResponse
    {
        $request->validate([
            'status'        => 'required|in:1,2',
            'note'          => 'nullable|string',
            'owner_meeting' => 'nullable|date',
        ]);

        $verifiedBy = $request->user() ? ($request->user()->name ?? $request->user()->id) : 'owner';

        $affected = DB::update("
            UPDATE mom_issue_item
            SET
                owner_verified_status = ?,
                owner_verified_note   = ?,
                owner_verified_by     = ?,
                owner_verified_at     = NOW(),
                owner_meeting         = ?
            WHERE id = ?
              AND id_mom IN (
                  SELECT id_mom FROM mom_header WHERE meeting = 'Owner' AND id_mom = ?
              )
        ", [
            $request->status,
            $request->note,
            $verifiedBy,
            $request->owner_meeting,
            $itemId,
            $idMom,
        ]);

        if (!$affected) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan atau bukan milik meeting Owner ini.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi owner berhasil disimpan.',
        ]);
    }
}