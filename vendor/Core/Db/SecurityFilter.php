<?php

namespace Core\Db;

class SecurityFilter
{
    private static function getMaliciousLabels(): array
    {
        return [
            'SELECT',
            '<script>',
            'UNION',
            'INSERT',
            'DELETE',
            'DROP',
            'EXECUTE',
            'JOIN'
        ];
    }

    private static function splitOutput($output): array |bool
    {
        return preg_split('/ /', $output, -1, PREG_SPLIT_OFFSET_CAPTURE);
    }

    private function registerAttack($attack): void
    {
        $log = new Logs();
        $log->toLog('REQUISIÇÃO BLOQUEADA: ' . base64_encode($attack));
    }

    public function secureData($output): bool
    {
        if (!empty($output) && !is_array($output)) {
            if (
                $output == strip_tags($output) &&
                filter_var($output, FILTER_UNSAFE_RAW)
            ) {
                $split_names = self::splitOutput($output);
                $malicious = self::getMaliciousLabels();

                foreach ($split_names as $key => $split_name) {
                    if (!empty($split_name[0])) {
                        if (in_array($split_name[0], $malicious)) {
                            $attack = htmlentities($output);
                            $this->registerAttack($attack);
                            return false;
                        }
                    }
                }

                return true;
            } else {
                $attack = htmlentities($output);
                $this->registerAttack($attack);

                return false;
            }
        } else {
            return true;
        }
    }
}