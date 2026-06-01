#!/bin/bash

echo "╔══════════════════════════════════════════════════════════╗"
echo "║  ISP BILLING SYSTEM - PARALLEL HTTP STRESS TEST         ║"
echo "║  Simulating concurrent browser sessions                  ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

BASE_URL="http://127.0.0.1:8000"
CONCURRENCY=${1:-20}
REQUESTS=${2:-100}
RESULTS_FILE="tests/pressure/results_parallel.txt"

echo "Config: ${CONCURRENCY} concurrent connections, ${REQUESTS} total requests"
echo "Target: ${BASE_URL}"
echo ""

> "$RESULTS_FILE"

run_endpoint_test() {
    local endpoint=$1
    local label=$2
    local count=$3
    
    echo "Testing: ${label} (${endpoint}) - ${count} requests..."
    
    local pids=()
    local success=0
    local fail=0
    local start=$(date +%s%N)
    
    local tmpdir=$(mktemp -d)
    
    for ((i=1; i<=count; i++)); do
        (
            local resp_code=$(curl -s -o /dev/null -w "%{http_code}" \
                --connect-timeout 5 --max-time 10 \
                "${BASE_URL}${endpoint}" 2>/dev/null)
            echo "$resp_code" > "${tmpdir}/result_${i}"
        ) &
        pids+=($!)
        
        if (( i % CONCURRENCY == 0 )); then
            for pid in "${pids[@]}"; do
                wait "$pid" 2>/dev/null
            done
            pids=()
        fi
    done
    
    for pid in "${pids[@]}"; do
        wait "$pid" 2>/dev/null
    done
    
    local end=$(date +%s%N)
    local elapsed_ms=$(( (end - start) / 1000000 ))
    
    local total=0
    local success=0
    local fail=0
    for f in ${tmpdir}/result_*; do
        if [ -f "$f" ]; then
            total=$((total + 1))
            local code=$(cat "$f")
            if [[ "$code" =~ ^[23] ]]; then
                success=$((success + 1))
            else
                fail=$((fail + 1))
            fi
        fi
    done
    
    local rps=0
    if [ $elapsed_ms -gt 0 ]; then
        rps=$(( total * 1000 / elapsed_ms ))
    fi
    
    local success_rate=0
    if [ $total -gt 0 ]; then
        success_rate=$(( success * 100 / total ))
    fi
    
    local status="✅"
    if [ $success_rate -lt 95 ]; then
        status="❌"
    elif [ $success_rate -lt 99 ]; then
        status="⚠️"
    fi
    
    printf "  ${status} %-30s %5d/%5d (%3d%%) %5dms %4d req/s\n" \
        "${label}" $success $total $success_rate $elapsed_ms $rps
    
    echo "${label}: success=${success}/${total} (${success_rate}%), time=${elapsed_ms}ms, rps=${rps}" >> "$RESULTS_FILE"
    
    rm -rf "$tmpdir"
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "ENDPOINT STRESS TEST"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
printf "  %-3s %-30s %5s/%5s (%5s) %7s %9s\n" "ST" "Endpoint" "OK" "Total" "Rate" "Time" "Req/s"
echo "  ──────────────────────────────────────────────────────────"

run_endpoint_test "/" "Homepage" $REQUESTS
run_endpoint_test "/login" "Login Page" $REQUESTS
run_endpoint_test "/health" "Health Check" $REQUESTS
run_endpoint_test "/check" "Self-Service Portal" $REQUESTS
run_endpoint_test "/apply" "New Application" $REQUESTS

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "RAPID-FIRE LOGIN ATTEMPTS (Simulating brute force)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

run_endpoint_test "/login" "Login Flood" 200

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "CONCURRENT API CALLS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

run_endpoint_test "/api/public/areas-by-zone/1" "Zone API" $((REQUESTS / 2))

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STRESS TEST COMPLETE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Results saved to: ${RESULTS_FILE}"
