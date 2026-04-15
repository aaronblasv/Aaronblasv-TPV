import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';
import { TaxesPage } from './taxes.page';

describe('TaxesPage', () => {
  let component: TaxesPage;
  let fixture: ComponentFixture<TaxesPage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TaxesPage],
      providers: [provideRouter([]), provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    fixture = TestBed.createComponent(TaxesPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
