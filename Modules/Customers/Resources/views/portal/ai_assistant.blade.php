@extends('customers::portal.layout')

@section('title', 'Dealer AI Assistant')

@section('body')
@include('customers::portal.partials_nav')

<div class="dd-wrap">
    <div class="dd-summary">
        <div class="dd-summary-item">
            <div class="dd-summary-label">Outstanding</div>
            <div class="dd-summary-value">{{ number_format($dashboard['summary']['outstanding_amount'] ?? $dashboard['summary']['current_balance'] ?? 0, 2) }}</div>
        </div>
        <div class="dd-summary-item">
            <div class="dd-summary-label">Available Credit</div>
            <div class="dd-summary-value">{{ number_format($dashboard['summary']['available_credit'] ?? 0, 2) }}</div>
        </div>
        <div class="dd-summary-item">
            <div class="dd-summary-label">Credit Usage</div>
            <div class="dd-summary-value">{{ number_format($dashboard['credit_utilization'] ?? 0, 2) }}%</div>
        </div>
        <div class="dd-summary-item">
            <div class="dd-summary-label">Open Orders</div>
            <div class="dd-summary-value">{{ $dashboard['open_orders'] ?? 0 }}</div>
        </div>
        <div class="dd-summary-item">
            <div class="dd-summary-label">Unread Alerts</div>
            <div class="dd-summary-value">{{ $dashboard['unread_notifications'] ?? 0 }}</div>
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header">
            <h3 class="dd-card-title"><i class="fa fa-comments"></i> Dealer AI Assistant</h3>
        </div>
        <div class="dd-card-body">
            <p style="color:#64748b;font-weight:700;margin-bottom:14px;">
                Ask about your outstanding balance, credit limit, invoices, payments, orders, deliveries, rewards, campaigns, or statement.
            </p>

            <form method="GET" action="{{ route('customers.portal.ai') }}" class="dd-filter">
                <div class="form-group" style="flex:1;min-width:260px;">
                    <label>Question</label>
                    <input type="text" name="question" value="{{ request('question') }}" class="form-control" placeholder="Example: What is my outstanding balance?">
                </div>
                <button type="submit" class="dd-btn dd-btn-primary">Ask</button>
                <a href="{{ route('customers.portal.ai') }}" class="dd-btn dd-btn-default">Clear</a>
            </form>

            @if(!empty($response))
                <div class="dd-list-item" style="border-left:5px solid #2563eb;">
                    <h4 class="dd-list-title">{{ $response['title'] ?? 'Assistant Answer' }}</h4>
                    <p style="font-size:15px;line-height:1.6;margin-bottom:12px;">{{ $response['answer'] ?? '' }}</p>
                    @if(!empty($response['cards']))
                        <div class="dd-summary" style="grid-template-columns:repeat(3,1fr);margin-bottom:0;">
                            @foreach($response['cards'] as $card)
                                <div class="dd-summary-item">
                                    <div class="dd-summary-label">{{ $card['label'] ?? '' }}</div>
                                    <div class="dd-summary-value">{{ $card['value'] ?? '' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header">
            <h3 class="dd-card-title"><i class="fa fa-lightbulb-o"></i> Smart Recommendations</h3>
        </div>
        <div class="dd-card-body">
            @forelse(($dashboard['recommendations'] ?? []) as $item)
                @php
                    $level = $item['level'] ?? 'info';
                    $badgeClass = $level === 'danger' ? 'dd-aging-danger' : ($level === 'warning' ? 'dd-aging-warning' : ($level === 'success' ? 'dd-aging-good' : 'dd-badge-open'));
                @endphp
                <div class="dd-list-item">
                    <div class="dd-list-meta"><span class="dd-aging {{ $badgeClass }}">{{ $item['type'] ?? 'Recommendation' }}</span></div>
                    <div style="font-weight:700;color:#334155;">{{ $item['message'] ?? '' }}</div>
                </div>
            @empty
                <div class="dd-empty">No recommendations available.</div>
            @endforelse
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header">
            <h3 class="dd-card-title"><i class="fa fa-question-circle"></i> Sample Questions</h3>
        </div>
        <div class="dd-card-body">
            @php
                $questions = [
                    'What is my outstanding balance?',
                    'How much credit do I have available?',
                    'Which invoices are overdue?',
                    'What was my last payment?',
                    'Do I have pending orders?',
                    'What deliveries are in transit?',
                    'How many reward points do I have?',
                ];
            @endphp
            @foreach($questions as $question)
                <a class="dd-btn dd-btn-default" style="margin:0 8px 8px 0;" href="{{ route('customers.portal.ai', ['question' => $question]) }}">{{ $question }}</a>
            @endforeach
        </div>
    </div>
</div>
@endsection
