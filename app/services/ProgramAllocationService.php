<?php
/**
 * Program unit allocation tracking and reporting.
 * Tracks allocated vs distributed quantities and values per unit.
 */
class ProgramAllocationService
{
    /**
     * Get allocation summary for a program
     * Returns: [unit => ['allocated' => qty, 'distributed' => qty, 'value' => total, 'remaining' => qty], ...]
     */
    public static function summary(int $programId): array
    {
        $rows = Model::query(
            'SELECT unit, allocated_amount, distributed_amount, distributed_value 
             FROM program_unit_allocations 
             WHERE program_id = ? 
             ORDER BY unit',
            [$programId]
        )->fetchAll();
        
        $summary = [];
        foreach ($rows as $row) {
            $summary[$row['unit']] = [
                'allocated' => (float) $row['allocated_amount'],
                'distributed' => (float) $row['distributed_amount'],
                'value' => (float) $row['distributed_value'],
                'remaining' => (float) $row['allocated_amount'] - (float) $row['distributed_amount'],
            ];
        }
        return $summary;
    }

    /**
     * Get total allocated and distributed for a program
     */
    public static function totals(int $programId): array
    {
        $row = Model::query(
            'SELECT 
                SUM(allocated_amount) as total_allocated,
                SUM(distributed_amount) as total_distributed,
                SUM(distributed_value) as total_value
             FROM program_unit_allocations 
             WHERE program_id = ?',
            [$programId]
        )->fetch();
        
        return [
            'allocated' => (float) ($row['total_allocated'] ?? 0),
            'distributed' => (float) ($row['total_distributed'] ?? 0),
            'value' => (float) ($row['total_value'] ?? 0),
            'remaining' => ((float) ($row['total_allocated'] ?? 0)) - ((float) ($row['total_distributed'] ?? 0)),
        ];
    }

    /**
     * Check if allocation exists for a program+unit combination
     */
    public static function exists(int $programId, string $unit): bool
    {
        return (bool) Model::query(
            'SELECT 1 FROM program_unit_allocations WHERE program_id = ? AND unit = ? LIMIT 1',
            [$programId, $unit]
        )->fetch();
    }

    /**
     * Get remaining allocation for a unit (allocated - distributed)
     */
    public static function remaining(int $programId, string $unit): ?float
    {
        $row = Model::query(
            'SELECT allocated_amount - distributed_amount as remaining 
             FROM program_unit_allocations 
             WHERE program_id = ? AND unit = ? 
             LIMIT 1',
            [$programId, $unit]
        )->fetch();
        
        return $row ? (float) $row['remaining'] : null;
    }
}
